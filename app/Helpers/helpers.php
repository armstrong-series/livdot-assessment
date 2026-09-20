<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Auth\Access\AuthorizationException;
use App\Services\RoleService;


if (! function_exists('livdotResponse')) {
    function livdotResponse(
        mixed $data,
        int $statusCode = 200,
        ?string $message = null,
        bool $status = true,
        ?string $selfLink = null,
        array $headers = [],
        ?string $type = null,
    ): JsonResponse {
        if (! config('livdot.json_api.enabled')) {
            return response()->json([
                'message' => $message,
                'status'  => $status ? 'success' : 'error',
                'data'    => $data,
            ], $statusCode, $headers);
        }


        $formatRelationship = function (mixed $related): array {
            if ($related instanceof Collection) {
                return [
                    'data' => $related->map(
                        fn(Model $model): array => [
                            'type' => Str::plural(
                                Str::snake(class_basename($model))
                            ),
                            'id' => (string) $model->getKey(),
                        ]
                    )->values()->all(),
                ];
            }

            if ($related instanceof Model) {
                return [
                    'data' => [
                        'type' => Str::plural(
                            Str::snake(class_basename($related))
                        ),
                        'id' => (string) $related->getKey(),
                    ],
                ];
            }

            return [
                'data' => null,
            ];
        };

        $formatResource = function (Model|array $resource) use (
            $type,
            $formatRelationship
        ): array {
            if ($resource instanceof Model) {
                $relationships = [];

                foreach ($resource->getRelations() as $relationName => $related) {
                    $relationships[$relationName] = $formatRelationship($related);
                }

                return [
                    'type' => $type
                        ?? Str::plural(
                            Str::snake(class_basename($resource))
                        ),
                    'id' => (string) $resource->getKey(),
                    'attributes' => collect($resource->getAttributes())
                        ->except([
                            'id',
                            'created_at',
                            'updated_at',
                            'deleted_at',
                        ])
                        ->all(),
                    'relationships' => $relationships,
                ];
            }

            return [
                'type' => $resource['type']
                    ?? $type
                    ?? request()->input('data.type', 'generic_data'),
                'id' => isset($resource['id'])
                    ? (string) $resource['id']
                    : null,
                'attributes' => $resource['attributes'] ?? $resource,
                'relationships' => $resource['relationships'] ?? new stdClass,
            ];
        };

        $formattedData = $data instanceof Collection
            ? $data->map($formatResource)->values()->all()
            : $formatResource($data);

        return response()->json([
            'message' => $message,
            'status' => $status ? 'success' : 'error',
            'data' => $formattedData,
            'included' => [],
            'meta' => [],
            'jsonapi' => ['version' => config('livdot.json_api.version')],
            'links' => ['self' => $selfLink ?? app('url')->current()],
        ], $statusCode, array_merge(['Content-Type' => 'application/vnd.api+json'], $headers));
    }
}


// if (! function_exists('authorizedRole')) {
//     function authorizedRole(string|array $roles, $user = null): void
//     {
//         $user = $user ?? auth()->user();

//         abort_unless($user, 401, 'Unauthenticated.');

//         if (in_array($user->role, (array) $roles, true)) {
//             return;
//         }

//         throw new AuthorizationException(
//             'Only ' . implode(', ', (array) $roles) . ' authorized action!'
//         );
//     }
// }



if (! function_exists('authorizedRole')) {

    function authorizedRole(string|array $roles, $user = null): void
    {
        $user = $user ?? auth()->user();
        $roleService = app()->make(RoleService::class);

        foreach ((array) $roles as $role) {
            if ($roleService->userHasRole($user, $role)) {
                return;
            }
        }

        throw new AuthorizationException('Only ' . implode(', ', (array) $roles) . ' authorized action!');
    }
}
