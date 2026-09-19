# LIV DOT — Live Event Streaming & Ticketing API

LIV DOT is a Laravel-based API for managing paid live-streamed events.

The platform supports:

-   Host registration and authentication
-   Paid event creation
-   Production crew assignment
-   Crew availability confirmation
-   Ticket reservations
-   External payment confirmation through webhooks
-   Idempotent ticket purchases and payment processing
-   Live-stream state transitions
-   Stream failure reporting
-   Automatic refunds for early stream failures
-   Administrative review for failures occurring after the automatic-refund threshold
-   Event completion
-   Host payouts
-   Financial ledger tracking

The implementation is designed around transactional state changes, financial integrity, idempotency, and failure-safe processing.

---

# 1. Requirements

Recommended development environment:

-   PHP 8.4+
-   Laravel 13+
-   Composer
-   MySQL 8+
-   Redis (if queue/background processing is enabled)
-   Git
-   Postman or another HTTP client

Check the installed versions:

```bash
php -v
composer -V
php artisan --version
```

---

# 2. Installation

Clone the repository:

```bash
git clone <repository-url>
cd livdot
```

Install PHP dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Configure the database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=livdot
DB_USERNAME=root
DB_PASSWORD=
```

Configure the application URL:

```env
APP_NAME="LIV DOT"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
```

Run migrations:

```bash
php artisan migrate
```

If seeders are available:

```bash
php artisan db:seed
```

Clear cached configuration during development when environment/configuration changes:

```bash
php artisan optimize:clear
```

Start the application:

```bash
php artisan serve
```

The API will normally be available at:

```text
http://127.0.0.1:8000
```

---

# 3. API Design

The API follows **JSON:API 1.1** conventions.

Requests containing resources use:

```http
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

Example request:

```json
{
    "data": {
        "type": "events",
        "attributes": {
            "title": "Lagos Tech Conference",
            "ticket_price_kobo": 3500000,
            "scheduled_duration_minutes": 120,
            "scheduled_starts_at": "2026-10-15T18:00:00+01:00"
        }
    }
}
```

Resource objects use:

```json
{
    "type": "events",
    "id": "event-uuid",
    "attributes": {
        "title": "Lagos Tech Conference"
    }
}
```

Relationships use JSON:API resource linkage:

```json
{
    "relationships": {
        "host": {
            "data": {
                "type": "users",
                "id": "user-uuid"
            }
        }
    }
}
```

---

# 4. Base URL

The application currently does not use an `/api` prefix.

For local development:

```text
http://127.0.0.1:8000
```

Therefore:

```text
POST /auth/login
```

rather than:

```text
POST /api/auth/login
```

---

# 5. Authentication

Protected endpoints use JWT authentication.

The login endpoint returns an authentication token.

For protected endpoints:

```http
Authorization: Bearer <JWT_TOKEN>
```

Example:

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOi...
```

The following operations require authentication:

-   Event creation
-   Crew assignment
-   Crew acceptance
-   Starting a broadcast
-   Completing a broadcast
-   Ticket purchase
-   Stream-failure reporting
-   Payout creation

The payment provider webhook is intentionally public and should authenticate the payment provider through webhook-signature verification in a production integration.

---

# 6. Common Headers

For JSON:API requests:

```http
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json
```

For protected endpoints:

```http
Authorization: Bearer <JWT_TOKEN>
```

For idempotent operations such as ticket purchasing:

```http
Idempotency-Key: <unique-operation-key>
```

---

# 7. Event Lifecycle

An event follows this lifecycle:

```text
scheduled
    |
    | begin broadcast
    v
live
    |
    | complete broadcast
    v
completed
```

An event should only become `live` after the required production crew assignment has been accepted.

Ticket purchasing is permitted while the event is:

```text
scheduled
```

or:

```text
live
```

Ticket purchasing is rejected for terminal states such as:

```text
completed
```

---

# 8. Financial Lifecycle

A ticket purchase and payment confirmation are separate operations.

## Initial purchase

```text
Ticket
    pending

PaymentTransaction
    pending
```

## Payment webhook

After a valid payment webhook:

```text
PaymentTransaction
    captured

Ticket
    active

LedgerEntry
    ticket_sale
```

This separation allows the API to safely handle asynchronous payment providers.

---

# 9. Idempotency

Ticket purchases use the `Idempotency-Key` HTTP header.

Example:

```http
Idempotency-Key: ticket-purchase-001
```

The key represents the logical operation, not the payment-provider reference.

If the same request is retried with the same key, the existing ticket is returned instead of creating another ticket.

Example:

```text
Request 1
Idempotency-Key: ticket-purchase-001
        |
        v
Create ticket
        |
        v
Store resource_id against idempotency key


Request 2
Idempotency-Key: ticket-purchase-001
        |
        v
Existing idempotency record found
        |
        v
Return existing ticket
```

A new purchase operation must use a new idempotency key.

---

# 10. API Endpoints

The currently registered routes are:

```text
POST auth/login
POST auth/logout
POST auth/signup

POST crew/assignments/{assignment}/accept
POST crew/events/{event}/crew-assignments

POST events/host
POST events/{event}/broadcast/live
POST events/{event}/broadcast/complete

POST payments/webhook

POST payouts/events/{event}/payouts

POST stream/events/{event}/stream-incidents

POST tickets/events/{event}/tickets
```

---

# 11. Authentication — Signup

## Endpoint

```http
POST /auth/signup
```

## Authentication

Public.

## Headers

```http
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json
```

## Request

The exact registration attributes should match `RegisterRequest`.

Typical JSON:API structure:

```json
{
    "data": {
        "type": "users",
        "attributes": {
            "name": "John Doe",
            "email": "john@example.com",
            "password": "password",
            "password_confirmation": "password"
        }
    }
}
```

## Purpose

Creates a user account.

The resulting user can authenticate and receive a JWT token.

---

# 12. Authentication — Login

## Endpoint

```http
POST /auth/login
```

## Authentication

Public.

## Request

```json
{
    "data": {
        "type": "auth",
        "attributes": {
            "email": "john@example.com",
            "password": "password"
        }
    }
}
```

## Purpose

Authenticates the user and returns the JWT required by protected endpoints.

---

# 13. Authentication — Logout

## Endpoint

```http
POST /auth/logout
```

## Authentication

JWT required.

```http
Authorization: Bearer <JWT_TOKEN>
```

## Purpose

Invalidates/logs out the authenticated session according to the configured JWT authentication implementation.

---

# 14. Create Event

## Endpoint

```http
POST /events/host
```

## Authentication

JWT required.

## Headers

```http
Authorization: Bearer <JWT_TOKEN>
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json
```

## Request

```json
{
    "data": {
        "type": "events",
        "attributes": {
            "title": "Lagos Tech Conference 2026",
            "ticket_price_kobo": 3500000,
            "scheduled_duration_minutes": 120,
            "scheduled_starts_at": "2026-10-15T18:00:00+01:00"
        }
    }
}
```

## Parameters

| Parameter                    | Type     | Required | Description            |
| ---------------------------- | -------- | -------: | ---------------------- |
| `title`                      | string   |      Yes | Event title            |
| `ticket_price_kobo`          | integer  |      Yes | Ticket price in kobo   |
| `scheduled_duration_minutes` | integer  |      Yes | Planned event duration |
| `scheduled_starts_at`        | datetime |      Yes | Scheduled start time   |

## Event status

Newly planned events are created with:

```text
scheduled
```

Example:

```json
{
    "data": {
        "type": "events",
        "id": "019...",
        "attributes": {
            "title": "Lagos Tech Conference 2026",
            "ticket_price_kobo": 3500000,
            "scheduled_duration_minutes": 120,
            "scheduled_starts_at": "2026-10-15T18:00:00+01:00",
            "status": "scheduled"
        }
    }
}
```

---

# 15. Assign Production Crew

## Endpoint

```http
POST /crew/events/{event}/crew-assignments
```

## Authentication

JWT required.

## URL Parameters

| Parameter | Type | Description     |
| --------- | ---- | --------------- |
| `event`   | UUID | Live event UUID |

## Request

The crew member information should match the request validation implemented by the application.

JSON:API structure:

```json
{
    "data": {
        "type": "crew-assignments",
        "attributes": {
            "crew_member_id": "019..."
        }
    }
}
```

## Purpose

Assigns a production crew member to the event.

---

# 16. Accept Crew Assignment

## Endpoint

```http
POST /crew/assignments/{assignment}/accept
```

## Authentication

JWT required.

## URL Parameters

| Parameter    | Type | Description          |
| ------------ | ---- | -------------------- |
| `assignment` | UUID | Crew assignment UUID |

## Request

No attributes are required.

The request can contain an empty JSON:API document if the request handler supports an empty body.

Example:

```json
{
    "data": {
        "type": "crew-assignments",
        "id": "019..."
    }
}
```

## Purpose

Allows the assigned crew member to confirm availability.

The assignment moves to:

```text
accepted
```

The operation is idempotent. Repeating an already accepted assignment does not create another assignment.

---

# 17. Start Broadcast

## Endpoint

```http
POST /events/{event}/broadcast/live
```

## Authentication

JWT required.

## URL Parameters

| Parameter | Type | Description     |
| --------- | ---- | --------------- |
| `event`   | UUID | Live event UUID |

## Request

No additional attributes are required.

Example:

```json
{
    "data": {
        "type": "events",
        "id": "019..."
    }
}
```

## Preconditions

The event must be scheduled.

The required crew assignment must have been accepted.

## Result

The event changes:

```text
scheduled
    ↓
live
```

The event's `started_at` timestamp is recorded.

---

# 18. Purchase / Reserve Ticket

## Endpoint

```http
POST /tickets/events/{event}/tickets
```

## Authentication

JWT required.

## Headers

```http
Authorization: Bearer <JWT_TOKEN>
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json
Idempotency-Key: ticket-purchase-001
```

## URL Parameters

| Parameter | Type | Description           |
| --------- | ---- | --------------------- |
| `event`   | UUID | Event being purchased |

## Request Body

```json
{
    "data": {
        "type": "ticket-reservation",
        "attributes": {
            "provider_reference": "01a0b565-a0b5-700c-b5e4-71541ec57786"
        }
    }
}
```

## Body Parameters

| Parameter            | Type   | Required | Description                               |
| -------------------- | ------ | -------: | ----------------------------------------- |
| `provider_reference` | string |      Yes | Payment-provider transaction/reference ID |

## Header Parameters

| Header            | Required | Description                                   |
| ----------------- | -------: | --------------------------------------------- |
| `Idempotency-Key` |      Yes | Unique identifier for this purchase operation |
| `Authorization`   |      Yes | JWT token                                     |
| `Accept`          |      Yes | JSON:API media type                           |
| `Content-Type`    |      Yes | JSON:API media type                           |

## Important distinction

`provider_reference` and `Idempotency-Key` have different purposes.

### Provider reference

```text
01a0b565-a0b5-700c-b5e4-71541ec57786
```

Identifies the payment transaction with the external payment provider.

### Idempotency key

```text
ticket-purchase-001
```

Identifies the API operation so retries do not create duplicate resources.

---

# 19. Ticket Purchase Processing

The purchase action performs the operation inside a database transaction.

Conceptually:

```text
Validate request
       |
       v
Validate Idempotency-Key
       |
       v
Find/create idempotency record
       |
       v
Lock event
       |
       v
Verify event is scheduled/live
       |
       v
Create ticket
       |
       v
Create pending PaymentTransaction
       |
       v
Store ticket ID on idempotency record
       |
       v
Commit
```

Immediately after the purchase:

```text
Ticket
status = pending
```

and:

```text
PaymentTransaction
status = pending
```

This is expected.

The ticket should not become active merely because the purchase endpoint was called.

---

# 20. Payment Webhook

## Endpoint

```http
POST /payments/webhook
```

## Authentication

Public endpoint.

The production implementation should validate the payment provider's webhook signature before accepting the event.

## Headers

```http
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json
```

No JWT should be required for a provider webhook.

## Request

```json
{
    "data": {
        "type": "payment-transactions",
        "attributes": {
            "provider_event_id": "evt_123456",
            "provider_reference": "01a0b565-a0b5-700c-b5e4-71541ec57786"
        }
    }
}
```

## Parameters

| Parameter            | Type   | Required | Description                                     |
| -------------------- | ------ | -------: | ----------------------------------------------- |
| `provider_event_id`  | string |      Yes | Unique event ID generated by payment provider   |
| `provider_reference` | string |      Yes | Existing payment reference from ticket purchase |

The `provider_reference` must match the value stored in:

```text
payment_transactions.provider_reference
```

---

# 21. Payment Capture Flow

The webhook performs an atomic payment capture.

Before webhook:

```text
PaymentTransaction
status = pending

Ticket
status = pending
```

After successful webhook:

```text
PaymentTransaction
status = captured
provider_event_id = evt_123456
captured_at = <timestamp>

Ticket
status = active
granted_at = <timestamp>

LedgerEntry
entry_type = ticket_sale
```

The financial operations occur within a database transaction.

Conceptually:

```text
Payment webhook
      |
      v
Find PaymentTransaction
      |
      v
Lock payment row
      |
      v
Already captured?
   /       \
 yes        no
 |           |
return      continue
             |
             v
Check provider event ID
             |
             v
Mark payment captured
             |
             v
Activate ticket
             |
             v
Create ticket_sale ledger entry
             |
             v
Commit transaction
```

---

# 22. Payment Webhook Idempotency

The same payment webhook may be delivered more than once by a payment provider.

The capture operation therefore checks:

```text
payment.status === captured
```

If already captured, the existing payment is returned without creating another financial ledger entry.

Example:

```text
Webhook 1
evt_123456
    ↓
Payment captured
    ↓
Ticket activated
    ↓
ticket_sale created


Webhook 2
evt_123456
    ↓
Payment already captured
    ↓
No duplicate ticket_sale
```

This prevents duplicate financial records caused by webhook retries.

---

# 23. Stream Failure Reporting

## Endpoint

```http
POST /stream/events/{event}/stream-incidents
```

## Authentication

JWT required.

## URL Parameters

| Parameter | Type | Description                   |
| --------- | ---- | ----------------------------- |
| `event`   | UUID | Event whose stream has failed |

## Request

Example:

```json
{
    "data": {
        "type": "stream-incidents",
        "attributes": {}
    }
}
```

The failure timestamp is generated by the server.

---

# 24. Stream Failure and the 25% Rule

The assessment requires a specific automatic-refund rule:

> If the stream fails before 25% of the scheduled event duration has elapsed, viewers are eligible for an automatic full refund.

The threshold is calculated from:

```text
scheduled_duration_minutes × 25%
```

Example:

```text
Scheduled duration = 120 minutes

120 × 25%
= 30 minutes
```

Therefore:

```text
Failure at 10 minutes
→ automatic refunds eligible

Failure at 20 minutes
→ automatic refunds eligible

Failure at 29 minutes
→ automatic refunds eligible

Failure at exactly 30 minutes
→ not before 25%

Failure at 45 minutes
→ admin review required
```

The implementation uses a strict `<` comparison, meaning exactly 25% is not considered "before 25%."

---

# 25. Early Stream Failure

When a stream fails before the threshold:

```text
Event
    live

StreamIncident
    automatic_refunds_eligible = true
```

Active tickets become eligible for full refunds.

Refund records are created for the ticket amount.

Example:

```text
Ticket amount
₦35,000

Refund
₦35,000
```

The corresponding financial ledger records the refund reserve.

Conceptually:

```text
Ticket Sale
+35,000

Refund Reserve
-35,000

Net payable
0
```

---

# 26. Stream Failure After 25%

If the stream fails at or after 25% of the scheduled duration:

```text
automatic_refunds_eligible = false
```

No automatic refund should be created by the stream-failure operation.

Instead, the incident should enter the administrative review workflow.

Conceptually:

```text
Stream failure
      |
      v
Calculate elapsed duration
      |
      +-----------------------------+
      |                             |
      | < 25%                       | >= 25%
      v                             v
Automatic refund              Admin review
eligible                       required
      |                             |
      v                             v
Refund active tickets        Admin decides
```

This distinction is important because the business rule does not provide automatic refunds after the threshold.

---

# 27. Refund Integrity

Refund processing must be idempotent.

A ticket must not receive multiple refunds for the same stream incident.

The refund record is associated with:

```text
ticket_id
stream_incident_id
amount_kobo
reason
```

The refund amount is based on the ticket amount rather than being recalculated from potentially mutable event data.

---

# 28. Complete Broadcast

## Endpoint

```http
POST /events/{event}/broadcast/complete
```

## Authentication

JWT required.

## URL Parameters

| Parameter | Type | Description |
| --------- | ---- | ----------- |
| `event`   | UUID | Event UUID  |

## Request

```json
{
    "data": {
        "type": "events",
        "id": "019..."
    }
}
```

## Result

The event moves:

```text
live
    ↓
completed
```

The server records:

```text
completed_at
```

Only appropriate event states should be allowed to transition to completed.

---

# 29. Payout

## Endpoint

```http
POST /payouts/events/{event}/payouts
```

## Authentication

JWT required.

## URL Parameters

| Parameter | Type | Description          |
| --------- | ---- | -------------------- |
| `event`   | UUID | Completed event UUID |

## Request

```json
{
    "data": {
        "type": "payouts",
        "attributes": {}
    }
}
```

## Preconditions

The event must be:

```text
completed
```

A payout cannot be created while the event is still:

```text
scheduled
```

or:

```text
live
```

---

# 30. Payout Calculation

The payout amount is derived from the event's financial ledger.

The intended calculation is:

```text
payable amount
=
ticket sales
-
refund reserves
```

For example:

```text
Ticket sales       10,500,000 kobo
Refund reserves     3,500,000 kobo
----------------------------------
Payable             7,000,000 kobo
```

The payout action must not simply sum every ledger entry because refund reserve entries must reduce the payable balance.

---

# 31. Payout Idempotency

Payout creation is idempotent for an event.

The event should have at most one payout record.

Conceptually:

```text
POST /payouts/events/{event}/payouts
          |
          v
Check event
          |
          v
Ensure completed
          |
          v
Calculate payable ledger balance
          |
          v
Find/create payout for event
```

Repeating the request should not create duplicate payouts.

---

# 32. Financial Ledger

The ledger provides the financial source of truth for event-level accounting.

Relevant entry types include:

```text
ticket_sale
refund_reserve
```

Example:

```text
ticket_sale       +3,500,000
ticket_sale       +3,500,000
ticket_sale       +3,500,000
refund_reserve    -3,500,000
--------------------------------
net payable       +7,000,000
```

All monetary values are stored as integer kobo rather than floating-point values.

This avoids floating-point rounding errors in financial calculations.

---

# 33. Event State Machine

The expected state flow is:

```text
                  +----------------+
                  |    scheduled   |
                  +----------------+
                          |
                          | crew accepted
                          | + start broadcast
                          v
                  +----------------+
                  |      live      |
                  +----------------+
                     |          |
                     |          |
              stream failure   complete
                     |          |
                     v          v
             stream incident  completed
```

Stream failure creates a separate `StreamIncident` rather than necessarily changing the event's primary state immediately.

This separates:

```text
Event lifecycle
```

from:

```text
Stream failure / financial consequence
```

---

# 34. Database Transactions

Operations affecting multiple related financial/state records should execute inside database transactions.

Examples:

## Ticket purchase

```text
IdempotencyKey
Ticket
PaymentTransaction
```

are created/updated atomically.

## Payment capture

```text
PaymentTransaction
Ticket
LedgerEntry
```

are updated/created atomically.

## Refund

```text
StreamIncident
Refund
Ticket
LedgerEntry
```

are handled atomically.

## Payout

```text
Payout
```

is calculated from the committed ledger state while locking the relevant event where necessary.

---

# 35. Row Locking

Critical state transitions use database row locks.

For example:

```php
LiveEvent::lockForUpdate()
```

This prevents concurrent requests from simultaneously changing the same event.

Payment capture similarly locks:

```php
PaymentTransaction::lockForUpdate()
```

This is important for race conditions such as:

```text
Webhook A
       \
        -> same payment
       /
Webhook B
```

Only one transaction should be able to perform the state transition.

---

# 36. Race Conditions

The system is designed to protect against several common race conditions.

## Duplicate ticket purchase

Protected by:

```text
Idempotency-Key
```

and database constraints.

## Duplicate payment webhook

Protected by:

```text
PaymentTransaction.status
provider_event_id
database transaction
row locking
```

## Concurrent event state changes

Protected by:

```text
LiveEvent::lockForUpdate()
```

## Duplicate refund

Protected by finding/creating the refund using the relevant ticket/incident identity.

## Duplicate payout

Protected by one payout per event.

---

# 37. JSON:API Response Structure

Successful responses use a structure such as:

```json
{
    "message": "Operation successful",
    "status": "success",
    "data": {
        "type": "tickets",
        "id": "019...",
        "attributes": {
            "live_event_id": "019...",
            "viewer_id": "019...",
            "amount_kobo": 3500000,
            "status": "pending"
        },
        "relationships": {}
    },
    "included": [],
    "meta": {},
    "jsonapi": {
        "version": "1.1"
    },
    "links": {
        "self": "http://127.0.0.1:8000/tickets/events/019.../tickets"
    }
}
```

The API explicitly identifies:

```json
"jsonapi": {
    "version": "1.1"
}
```

---

# 38. JSON:API Resource Types

The application uses resource types such as:

```text
users
events
crew-assignments
tickets
payment-transactions
stream-incidents
refunds
payouts
ledger-entries
```

The resource type should remain consistent between requests and responses.

For strict JSON:API compliance, resource type naming should use one consistent convention throughout the application.

---

# 39. JSON:API Relationships

Relationships should use resource linkage rather than embedding unrelated complete resources.

Example:

```json
{
    "data": {
        "type": "tickets",
        "id": "019-ticket",
        "attributes": {
            "status": "active",
            "amount_kobo": 3500000
        },
        "relationships": {
            "event": {
                "data": {
                    "type": "events",
                    "id": "019-event"
                }
            },
            "viewer": {
                "data": {
                    "type": "users",
                    "id": "019-user"
                }
            }
        }
    }
}
```

If complete related resources are required, they belong in:

```json
"included": []
```

rather than being embedded directly inside the relationship.

---

# 40. Error Responses

Errors should use HTTP status codes appropriate to the failure.

Examples:

### Missing idempotency key

```http
422 Unprocessable Entity
```

Message:

```text
Idempotency-Key is required.
```

### Invalid event state

```http
422 Unprocessable Entity
```

Message:

```text
Tickets are not available for this event.
```

### Payment not found

```http
404 Not Found
```

### Duplicate/conflicting provider event

```http
409 Conflict
```

### Unauthorized request

```http
401 Unauthorized
```

### Forbidden operation

```http
403 Forbidden
```

---

# 41. Complete End-to-End Test Flow

The following sequence can be used to demonstrate the assessment.

## Step 1 — Create users

```text
POST /auth/signup
```

Create at least:

```text
Host
Viewer
Crew member
```

Authenticate each user.

---

## Step 2 — Login

```text
POST /auth/login
```

Store the JWT token.

---

## Step 3 — Host creates event

```text
POST /events/host
```

Example:

```json
{
    "data": {
        "type": "events",
        "attributes": {
            "title": "Lagos Tech Conference 2026",
            "ticket_price_kobo": 3500000,
            "scheduled_duration_minutes": 120,
            "scheduled_starts_at": "2026-10-15T18:00:00+01:00"
        }
    }
}
```

Expected:

```text
event.status = scheduled
```

Save:

```text
event.id
```

---

## Step 4 — Assign crew

```text
POST /crew/events/{event}/crew-assignments
```

Assign the production crew member.

Save:

```text
assignment.id
```

---

## Step 5 — Crew accepts assignment

```text
POST /crew/assignments/{assignment}/accept
```

Expected:

```text
assignment.status = accepted
```

---

## Step 6 — Start broadcast

```text
POST /events/{event}/broadcast/live
```

Expected:

```text
event.status = live
event.started_at = <timestamp>
```

---

## Step 7 — Purchase ticket

```text
POST /tickets/events/{event}/tickets
```

Headers:

```http
Authorization: Bearer <VIEWER_JWT>
Idempotency-Key: ticket-purchase-001
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json
```

Body:

```json
{
    "data": {
        "type": "ticket-reservation",
        "attributes": {
            "provider_reference": "01a0b565-a0b5-700c-b5e4-71541ec57786"
        }
    }
}
```

Expected database state:

```text
Ticket
status = pending

PaymentTransaction
status = pending
provider_reference = 01a0b565-a0b5-700c-b5e4-71541ec57786
```

---

## Step 8 — Confirm payment

```text
POST /payments/webhook
```

Body:

```json
{
    "data": {
        "type": "payment-transactions",
        "attributes": {
            "provider_event_id": "evt_123456",
            "provider_reference": "01a0b565-a0b5-700c-b5e4-71541ec57786"
        }
    }
}
```

Expected:

```text
PaymentTransaction
status = captured

Ticket
status = active

LedgerEntry
entry_type = ticket_sale
```

---

## Step 9 — Repeat webhook

Send the exact same webhook again.

Expected:

```text
No duplicate ticket
No duplicate ledger entry
Payment remains captured
```

This demonstrates webhook idempotency.

---

# 42. Test the 25% Refund Rule

For a 120-minute event:

```text
25% = 30 minutes
```

To test an early failure, report the failure before 30 minutes have elapsed.

```text
POST /stream/events/{event}/stream-incidents
```

Expected:

```text
StreamIncident
automatic_refunds_eligible = true
```

Active tickets receive full refunds.

Expected financial result:

```text
ticket_sale
-
refund_reserve
=
net payable
```

---

# 43. Test Late Stream Failure

For the same 120-minute event, report the stream failure at or after the 30-minute threshold.

Expected:

```text
automatic_refunds_eligible = false
```

No automatic refund should be generated.

The incident should be available for the administrative review workflow.

---

# 44. Complete Event

```text
POST /events/{event}/broadcast/complete
```

Expected:

```text
event.status = completed
event.completed_at = <timestamp>
```

---

# 45. Create Payout

```text
POST /payouts/events/{event}/payouts
```

The payout should be based on:

```text
ticket sales - refund reserves
```

Example:

```text
Sales:
10,500,000 kobo

Refund reserves:
3,500,000 kobo

Payout:
7,000,000 kobo
```

A second payout request for the same event must not create a duplicate payout.

---

# 46. Recommended Postman Environment

Create a Postman environment with:

```text
base_url
host_token
viewer_token
crew_token
event_id
assignment_id
ticket_id
payment_reference
provider_event_id
```

Example:

```text
base_url = http://127.0.0.1:8000
```

Then requests can use:

```text
{{base_url}}/events/host
```

and:

```text
{{base_url}}/tickets/events/{{event_id}}/tickets
```

---

# 47. Example Postman Ticket Purchase

## URL

```text
{{base_url}}/tickets/events/{{event_id}}/tickets
```

## Method

```text
POST
```

## Headers

```text
Authorization: Bearer {{viewer_token}}
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json
Idempotency-Key: ticket-purchase-001
```

## Body

```json
{
    "data": {
        "type": "ticket-reservation",
        "attributes": {
            "provider_reference": "{{payment_reference}}"
        }
    }
}
```

---

# 48. Example Postman Payment Webhook

## URL

```text
{{base_url}}/payments/webhook
```

## Method

```text
POST
```

## Headers

```text
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json
```

## Body

```json
{
    "data": {
        "type": "payment-transactions",
        "attributes": {
            "provider_event_id": "{{provider_event_id}}",
            "provider_reference": "{{payment_reference}}"
        }
    }
}
```

The `payment_reference` must be the same reference created during ticket reservation.

---

# 49. Useful Laravel Commands

List all routes:

```bash
php artisan route:list
```

List only event routes:

```bash
php artisan route:list --path=events
```

List payment routes:

```bash
php artisan route:list --path=payments
```

List ticket routes:

```bash
php artisan route:list --path=tickets
```

Run migrations:

```bash
php artisan migrate
```

Reset and migrate:

```bash
php artisan migrate:fresh
```

Clear application caches:

```bash
php artisan optimize:clear
```

Run tests:

```bash
php artisan test
```

Open Tinker:

```bash
php artisan tinker
```

---

# 50. Useful Database Verification

After ticket purchase:

```php
PaymentTransaction::latest()->first();
```

Check ticket:

```php
Ticket::latest()->first();
```

After payment webhook:

```php
PaymentTransaction::latest()->first()->status;
```

Expected:

```text
captured
```

Check ticket:

```php
Ticket::latest()->first()->status;
```

Expected:

```text
active
```

Check ledger:

```php
LedgerEntry::latest()->first();
```

Expected:

```text
ticket_sale
```

Check stream incidents:

```php
StreamIncident::latest()->first();
```

Check refunds:

```php
Refund::latest()->first();
```

Check payout:

```php
Payout::latest()->first();
```

---

# 51. Current Route Map

The application's current route list is:

| Method | Endpoint                                  | Controller action                              |
| ------ | ----------------------------------------- | ---------------------------------------------- |
| POST   | `/auth/login`                             | `Auth\AuthController@authenticate`             |
| POST   | `/auth/logout`                            | `Auth\AuthController@logout`                   |
| POST   | `/auth/signup`                            | `Auth\AuthController@register`                 |
| POST   | `/crew/assignments/{assignment}/accept`   | `CrewAssignmentController@confirmAvailability` |
| POST   | `/crew/events/{event}/crew-assignments`   | `CrewAssignmentController@assignCrew`          |
| POST   | `/events/host`                            | `EventController@hostEvent`                    |
| POST   | `/events/{event}/broadcast/complete`      | `EventController@finalizeBroadcast`            |
| POST   | `/events/{event}/broadcast/live`          | `EventController@beginBroadcast`               |
| POST   | `/payments/webhook`                       | `TicketController@confirmPayment`              |
| POST   | `/payouts/events/{event}/payouts`         | `PayoutController@preparePayout`               |
| POST   | `/stream/events/{event}/stream-incidents` | `StreamController@recordFailure`               |
| POST   | `/tickets/events/{event}/tickets`         | `TicketController@reserveAccess`               |

There is intentionally no `/api` prefix in the current route configuration.

---

# 52. Architecture

The application follows a simple layered architecture:

```text
HTTP Request
     |
     v
FormRequest
     |
     v
Controller
     |
     v
Service
     |
     v
Action
     |
     v
Models / Database
```

The preferred responsibility boundaries are:

### FormRequest

Responsible for:

-   Validation
-   Request authorization
-   JSON:API request normalization

### Controller

Responsible for:

-   Receiving the request
-   Passing validated input to the Service
-   Returning the API response

### Service

Responsible for:

-   Orchestrating the application operation
-   Calling the appropriate Action

### Action

Responsible for:

-   Business implementation
-   Transactions
-   State transitions
-   Locking
-   Financial integrity
-   Idempotency

### Model

Responsible for:

-   Persistence
-   Relationships
-   Casts
-   Model-level behavior

---

# 53. Business Rules

The main business rules are:

1. A planned event starts in `scheduled`.
2. A broadcast can transition from `scheduled` to `live`.
3. A broadcast can transition from `live` to `completed`.
4. Required crew availability must be confirmed before the broadcast begins.
5. Tickets can be purchased while an event is `scheduled` or `live`.
6. Ticket purchase creates a pending payment transaction.
7. Ticket access becomes active only after payment confirmation.
8. Payment webhooks are idempotent.
9. Ticket purchases are idempotent through `Idempotency-Key`.
10. Payment capture and ticket activation occur atomically.
11. Every successful ticket sale creates a financial ledger entry.
12. Stream failure before 25% of scheduled duration makes viewers eligible for automatic full refunds.
13. Stream failure at or after 25% requires administrative review rather than automatic refund.
14. Refunds must not be duplicated.
15. Payouts can only be generated for completed events.
16. Payout amount is based on ticket sales less refund reserves.
17. An event must not receive multiple payouts.
18. Monetary amounts are represented as integer kobo.
19. Critical state transitions use database transactions and row locking.
20. Provider references and provider event IDs are treated as external payment identifiers and must be handled idempotently.

---

# 54. Assessment Focus

The implementation specifically demonstrates:

### State management

```text
scheduled → live → completed
```

### Payment state management

```text
pending → captured
```

### Ticket state management

```text
pending → active
```

### Stream failure handling

```text
stream failure
      |
      +-- before 25% → automatic refund
      |
      +-- >= 25% → administrative review
```

### Financial accounting

```text
ticket sales - refunds = payable balance
```

### Idempotency

```text
ticket purchase
payment webhook
refund
payout
```

### Concurrency protection

```text
database transactions
row-level locking
unique business identifiers
```

These mechanisms are intended to prevent duplicate payments, duplicate tickets, duplicate refunds, duplicate payouts, and inconsistent event state.

---

# 55. Troubleshooting

## `Idempotency-Key is required`

Ensure the request contains:

```http
Idempotency-Key: ticket-purchase-001
```

It belongs in the HTTP headers, not the JSON body.

---

## `Tickets are not available for this event`

Check:

```php
$event->status;
```

The status must be:

```text
scheduled
```

or:

```text
live
```

---

## Payment webhook returns `404`

First verify the route:

```bash
php artisan route:list --path=payments
```

Expected:

```text
POST payments/webhook
```

Then verify that the payment reference exists:

```php
PaymentTransaction::where(
    'provider_reference',
    '01a0b565-a0b5-700c-b5e4-71541ec57786'
)->first();
```

The webhook's:

```text
provider_reference
```

must exactly match the payment transaction created during ticket purchase.

---

## Payment remains `pending`

The payment webhook has either:

-   not been received,
-   referenced the wrong `provider_reference`,
-   failed validation,
-   failed provider-event validation,
-   or failed before the capture transaction committed.

Check the application logs:

```bash
tail -f storage/logs/laravel.log
```

---

## Ticket remains `pending`

Check the associated payment:

```php
$ticket->paymentTransaction;
```

The ticket becomes active as part of successful payment capture.

---

# 56. Production Considerations

Before production deployment, the following should be added or verified:

-   Payment-provider webhook signature verification
-   Provider API verification where required
-   Database unique constraints for business identifiers
-   Proper queue configuration for asynchronous work
-   Retry/backoff policies for external providers
-   Structured application logging
-   Monitoring and alerting
-   Administrative refund-review endpoints
-   Authorization policies for administrative actions
-   HTTPS
-   Secure JWT configuration
-   Production database backups
-   Redis configuration where queues are used
-   Rate limiting on authentication and webhook endpoints
-   Secrets managed through environment variables/secrets management

Webhook endpoints should never trust arbitrary client-provided payment events in production without authenticating the payment provider.

---

# 57. Quick Reference

## Authentication

```text
POST /auth/signup
POST /auth/login
POST /auth/logout
```

## Events

```text
POST /events/host
POST /events/{event}/broadcast/live
POST /events/{event}/broadcast/complete
```

## Crew

```text
POST /crew/events/{event}/crew-assignments
POST /crew/assignments/{assignment}/accept
```

## Tickets

```text
POST /tickets/events/{event}/tickets
```

## Payments

```text
POST /payments/webhook
```

## Stream failures

```text
POST /stream/events/{event}/stream-incidents
```

## Payouts

```text
POST /payouts/events/{event}/payouts
```

---

# 58. End-to-End Summary

The complete LIV DOT workflow is:

```text
                     ┌──────────────┐
                     │    Signup    │
                     └──────┬───────┘
                            ↓
                     ┌──────────────┐
                     │    Login     │
                     └──────┬───────┘
                            ↓
                     ┌──────────────┐
                     │ Create Event │
                     └──────┬───────┘
                            ↓
                       scheduled
                            ↓
                     ┌──────────────┐
                     │ Assign Crew  │
                     └──────┬───────┘
                            ↓
                     ┌──────────────┐
                     │ Crew Accepts │
                     └──────┬───────┘
                            ↓
                     ┌──────────────┐
                     │  Go Live     │
                     └──────┬───────┘
                            ↓
                           live
                            ↓
                 ┌──────────────────────┐
                 │   Purchase Ticket    │
                 └──────────┬───────────┘
                            ↓
                    Ticket: pending
                            +
                 Payment: pending
                            ↓
                 ┌──────────────────────┐
                 │   Payment Webhook    │
                 └──────────┬───────────┘
                            ↓
                    Payment: captured
                            +
                      Ticket: active
                            +
                     Ledger: sale
                            ↓
                 ┌──────────────────────┐
                 │   Stream Failure?    │
                 └──────────┬───────────┘
                            │
              ┌─────────────┴─────────────┐
              ↓                           ↓
          Before 25%                  >= 25%
              ↓                           ↓
      Automatic refund             Admin review
              │                           │
              └─────────────┬─────────────┘
                            ↓
                    Complete Broadcast
                            ↓
                        completed
                            ↓
                         Payout
                            ↓
                  Sales - Refunds
                            ↓
                      Host payout
```

This represents the intended end-to-end business flow of the LIV DOT assessment.
