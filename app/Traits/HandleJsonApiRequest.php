<?php

namespace App\Traits;

use App\Enums\JsonApiVersion;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

trait HandleJsonApiRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->input('data');

        if (! is_array($data)) {
            return;
        }

        if (! isset($data['type'])) {
            abort(response()->json(
                [
                    'errors' => [
                        [
                            'status' => '400',
                            'title' => 'Invalid JSON:API request',
                            'details' => '`data.type` is required when `data` is present.',
                        ],
                    ],
                    'jsonapi' => [
                        'version' => JsonApiVersion::v1->value,
                    ],
                ],
                400
            ));
        }

        $this->merge([
            'type' => $data['type'],
        ]);

        if (isset($data['attributes']) && is_array($data['attributes'])) {
            $this->merge($data['attributes']);
        }

        if (isset($data['id'])) {
            $this->merge([
                'id' => $data['id'],
            ]);
        }

        if (isset($data['relationships']) && is_array($data['relationships'])) {
            $this->mergeRelationships($data['relationships']);

            foreach ($data['relationships'] as $relationship => $relData) {
                if (isset($relData['data']['id'])) {
                    $fieldName = $this->convertRelationshipToFieldName($relationship);

                    $this->merge([
                        $fieldName => (string) $relData['data']['id'],
                    ]);
                }
            }
        }
    }


    protected function isJsonApiRequest(): bool
    {
        return config('livdot.json_api.enabled') && is_array($this->input('data'));
    }

    protected function mergeRelationships(array $relationships): void
    {
        foreach ($relationships as $relationship => $data) {
            if (isset($data['data'])) {
                if (isset($data['data']['id'])) {
                    $fieldName = $this->convertRelationshipToFieldName($relationship);
                    $id = $data['data']['id'];

                    $this->merge([$fieldName => (string) $id]);
                }
            }
        }
    }

    protected function convertRelationshipToFieldName(string $relationship): string
    {
        $mappings = [
            'subscription' => 'subscription_id',
            'provider' => 'provider_id',
            'plan' => 'plan_id',
            'user' => 'user_id',
            'category' => 'category_id',
        ];

        return $mappings[$relationship] ?? str_replace('-', '_', $relationship) . '_id';
    }

    public function validatedAttributes(): array
    {
        $validated = $this->validated();

        return array_filter($validated, fn($key) => $key !== 'type', ARRAY_FILTER_USE_KEY);
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = collect($validator->errors()->toArray())
            ->map(fn($messages, $field) => [
                'status' => '422',
                'source' => ['pointer' => "/data/attributes/{$field}"],
                'title' => 'Invalid Attribute',
                'details' => $messages[0] ?? 'Invalid value provided.',
            ])
            ->values()
            ->toArray();

        throw new ValidationException($validator, response()->json([
            'errors' => $errors,
            'jsonapi' => [
                'version' => JsonApiVersion::v1->value,
            ],
        ], 422));
    }
}
