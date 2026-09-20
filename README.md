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
-   Queued and retryable refund processing
-   Administrative review for failures occurring after the automatic-refund threshold
-   Event completion
-   Host payouts
-   Financial ledger tracking

The implementation is designed around transactional state changes, financial integrity, idempotency, concurrency protection, and failure-safe asynchronous processing.

---

# 1. Requirements

Recommended development environment:

-   PHP 8.4+
-   Laravel 13+
-   Composer
-   MySQL 8+
-   Redis if queue/background processing is enabled
-   Git
-   Postman or another HTTP client
-   Docker (optional)

The application uses queues for asynchronous processing such as incident refund processing.

---

# 2. Installation

Clone the repository and install the Composer dependencies.

## Docker

```bash
docker compose up --build
```

## Local setup

This is without docker build

```bash
git clone <repository-url>
cd liv-dot
composer install
```

Create the environment file from the example environment file and configure the application and database connection.

```bash
cp .env.example .env
```

Generate the application key.

```bash
   php artisan key:generate
```

Configure the MySQL database and application URL in the environment configuration.

Run the database migrations.

```bash
   php artisan migrate
```

Run Host Seeded information

```bash
 php artisan db:seed
```

During development, cached configuration should be cleared whenever environment or configuration values are changed.

Start the Laravel application.

The API is normally available locally at:

http://127.0.0.1:8000

---

# 3. Fresh Development Database

When testing the complete application from the beginning, the recommended development process is to reset the database and run all seeders.

The fresh database process creates the required database structure and default development users.

The default development host is created by the seeder with:

-   Email: [host@livdot.io](mailto:host@livdot.io)
-   Password: password
-   Role: host

Use these credentials to authenticate through the login endpoint and obtain the host JWT.

The default host can then be used to:

-   Create events
-   Assign production crew
-   Start broadcasts for events owned by the host
-   Complete broadcasts
-   Perform other host-authorized operations

A separate viewer and crew user should be available when testing the complete event lifecycle. If they are not included in the default seed data, they can be created through the signup endpoint.

---

# 4. Queue Worker

The refund workflow uses a queued Job.

for local testing , run via terminal

```bash
php artisan queue:work
```

A queue worker must therefore be running when testing automatic refunds.

The important asynchronous component is:

ProcessIncidentRefundsJob

The Job is responsible for executing incident refund processing asynchronously and providing retry behavior when a temporary failure occurs.

The Job does not contain the refund business logic itself.

The refund business logic belongs to:

IssueIncidentRefundsAction

The relationship is:

ReportStreamFailureAction
→ StreamFailureReported event
→ StreamFailureReportedListener
→ ProcessIncidentRefundsJob
→ IssueIncidentRefundsAction

The Job is configured to retry failed processing up to three times with increasing backoff intervals.

---

# 5. API Design

The API follows JSON:API 1.1 conventions.

JSON requests and responses use the JSON:API media type.

Resource objects use a type, identifier, attributes, and relationships where applicable.

The API uses a consistent response structure containing the application message, status, resource data, included resources where required, metadata, JSON:API version information, and the request URL.

---

# 6. Base URL

The application currently does not use an `/api` prefix.

For local development the base URL is normally:

http://127.0.0.1:8000

Therefore endpoints use paths such as:

POST /auth/login

rather than:

POST /api/auth/login

---

# 7. Authentication

Protected endpoints use JWT authentication.

After successful login, the returned JWT is supplied using the Authorization Bearer header.

The main authenticated operations are:

-   Event creation
-   Crew assignment
-   Crew availability confirmation
-   Starting a broadcast
-   Completing a broadcast
-   Ticket purchase
-   Stream-failure reporting
-   Payout creation

The payment provider webhook is intentionally public because payment providers cannot normally authenticate using the application's JWT.

In production, the payment webhook must verify the payment provider's webhook signature before processing the event.

---

# 8. User Roles

LIV DOT currently uses a simple user role model.

The relevant roles are:

-   Host
-   Crew
-   Viewer

Roles determine the general category of operation a user can perform.

Ownership checks are also required for event-specific operations.

For example, being a host does not mean that the host can operate every event. A host should only be able to control events belonging to that host.

The application therefore uses both:

-   Role authorization
-   Resource ownership authorization

---

# 9. Host Authorization

Host operations are protected using the application's simple role authorization helper.

The helper checks the authenticated user's role without requiring a separate role service or role relationship.

For event-specific operations, role authorization is combined with event ownership.

For example:

A user must be a host.

The event must also belong to that host.

This prevents one host from starting, completing, or otherwise controlling another host's event.

---

# 10. Event Lifecycle

An event follows this lifecycle:

scheduled
→ live
→ completed

A new event begins in the scheduled state.

The event can only become live after the required crew assignment has been accepted.

The event can only be completed after it has become live.

The lifecycle is enforced through state-transition logic in the application Actions.

---

# 11. Crew Workflow

The crew workflow has two distinct responsibilities.

## Host

The host assigns a production crew member to the event.

The assignment initially represents a pending crew assignment.

## Crew member

The assigned crew member confirms availability.

The crew member must be the specific crew member attached to the assignment.

The assignment then becomes accepted.

The host can subsequently start the broadcast.

The complete flow is:

Host creates event
→ Host assigns crew
→ Crew assignment is pending
→ Assigned crew member confirms availability
→ Crew assignment becomes accepted
→ Host starts broadcast

The host does not accept the crew assignment on behalf of the crew member.

---

# 12. Authentication — Signup

Endpoint:

POST /auth/signup

This endpoint is public.

It creates a user account.

The resulting account can subsequently authenticate through the login endpoint.

The role assigned during registration should follow the application's registration rules.

For development testing, the default host is provided by the database seeder.

---

# 13. Authentication — Login

Endpoint:

POST /auth/login

This endpoint is public.

The user provides their login credentials and receives a JWT.

The JWT is then used for protected operations.

For a fresh development database, the default host credentials are:

Email:

[host@livdot.io](mailto:host@livdot.io)

Password:

password

Role:

host

---

# 14. Authentication — Logout

Endpoint:

POST /auth/logout

JWT authentication is required.

The endpoint invalidates or terminates the authenticated session according to the configured JWT authentication implementation.

---

# 15. Create Event

Endpoint:

POST /events/host

Authentication:

Host JWT required.

The authenticated user must have the host role.

The event is created through the LiveEvent service and its underlying Action.

The new event receives the scheduled state.

The host ID is associated with the event.

Important event attributes include:

-   Title
-   Ticket price in kobo
-   Scheduled duration in minutes
-   Scheduled start time

The resulting event state is:

scheduled

---

# 16. Assign Production Crew

Endpoint:

POST /crew/events/{event}/crew-assignments

Authentication:

Host JWT required.

The authenticated host must own the event.

The host selects the production crew member who should work on the event.

The resulting crew assignment starts in its pending state.

The assignment establishes the relationship between:

-   Event
-   Host
-   Assigned crew member

Only the event host should be allowed to assign crew to that event.

A host cannot assign crew to another host's event.

---

# 17. Confirm Crew Availability

Endpoint:

POST /crew/assignments/{assignment}/accept

Authentication:

Crew JWT required.

The authenticated user must be the crew member assigned to that particular assignment.

The request authorization is handled by the ConfirmCrewAvailabilityRequest.

The request verifies that the authenticated user's ID matches the crew member associated with the assignment.

The AcceptCrewAssignmentAction then changes the assignment state from pending to accepted.

If the assignment has already been accepted, the Action does not create another assignment or perform a duplicate state transition.

This operation is therefore idempotent.

The resulting workflow is:

Crew assignment
→ pending
→ assigned crew member confirms availability
→ accepted

---

# 18. Start Broadcast

Endpoint:

POST /events/{event}/broadcast/live

Authentication:

Host JWT required.

The authenticated user must:

-   Have the host role
-   Own the event

The event must currently be scheduled.

An accepted crew assignment must also exist.

If no accepted crew assignment exists, the operation is rejected.

The successful state transition is:

scheduled
→ live

The server records the event's started_at timestamp when the broadcast becomes live.

This timestamp is later used when determining how much of the scheduled event duration had elapsed when a stream failure occurred.

---

# 19. Purchase / Reserve Ticket

Endpoint:

POST /tickets/events/{event}/tickets

Authentication:

Viewer JWT required.

Ticket purchasing is allowed while the event is:

-   scheduled
-   live

Ticket purchasing is rejected once the event reaches a terminal state such as completed.

The purchase requires:

-   A payment provider reference
-   An Idempotency-Key HTTP header

The provider reference identifies the external payment transaction.

The Idempotency-Key identifies the API purchase operation.

These values have different purposes and should never be treated as interchangeable.

---

# 20. Ticket Purchase Flow

Ticket purchasing and payment confirmation are separate operations.

The purchase process creates:

Ticket
→ pending

PaymentTransaction
→ pending

The ticket does not become active merely because the purchase endpoint was called.

The payment must subsequently be confirmed through the payment webhook.

The purchase operation is performed transactionally so that the idempotency record, ticket, and payment transaction are kept consistent.

---

# 21. Idempotent Ticket Purchase

The Idempotency-Key prevents duplicate ticket purchases when a client retries the same operation.

The first request creates the ticket and associates the created ticket with the idempotency record.

If the same Idempotency-Key is submitted again, the existing ticket is returned.

This protects against situations such as:

-   Client timeout
-   Network retry
-   Duplicate client submission
-   Application-level retry

A new logical purchase operation must use a new Idempotency-Key.

---

# 22. Payment Webhook

Endpoint:

POST /payments/webhook

The webhook is public because it is called by the external payment provider.

The production implementation should verify the provider's webhook signature before accepting the request.

The webhook contains:

-   Provider event ID
-   Provider payment reference

The provider reference must correspond to an existing PaymentTransaction created during ticket reservation.

The webhook does not create an arbitrary ticket or payment transaction from scratch.

It confirms an existing pending payment.

---

# 23. Payment Capture

The payment capture process is handled transactionally.

The system:

1. Finds the payment using the provider reference.
2. Locks the payment record.
3. Checks whether the payment has already been captured.
4. Validates that the provider event has not already been associated with another payment.
5. Marks the payment as captured.
6. Activates the associated ticket.
7. Records the ticket access timestamp.
8. Creates the ticket sale ledger entry.
9. Commits the transaction.
10. Dispatches the PaymentCaptured event.

The resulting state is:

PaymentTransaction
→ captured

Ticket
→ active

Ledger
→ ticket_sale

---

# 24. Payment Webhook Idempotency

Payment providers may deliver the same webhook more than once.

The payment capture operation is therefore idempotent.

If the payment is already captured, the system returns the existing payment without creating another financial ledger entry.

The provider event ID is also checked to prevent the same external event from being associated with another payment.

This protects against duplicate:

-   Payment captures
-   Ticket activation
-   Ticket sale ledger entries

The key principle is:

Retries may happen, but retries must not create duplicate financial state.

---

# 25. Stream Failure Reporting

Endpoint:

POST /stream/events/{event}/stream-incidents

Authentication:

JWT required.

The endpoint reports that a live event has experienced a stream failure.

The failure timestamp is generated by the server.

The operation is handled by:

ReportStreamFailureAction

This Action is responsible for recording the incident and determining whether the incident qualifies for automatic refunds.

It does not perform the actual refund processing.

---

# 26. ReportStreamFailureAction

ReportStreamFailureAction has a focused responsibility.

It:

1. Locks the event.
2. Confirms that the event is currently live.
3. Confirms that the event has a started_at timestamp.
4. Records the failure timestamp.
5. Calculates how much scheduled event time has elapsed.
6. Calculates the automatic-refund threshold.
7. Determines whether the incident is eligible for automatic refunds.
8. Creates the StreamIncident.
9. Dispatches the StreamFailureReported event after the transaction succeeds.

The Action therefore records the business fact:

A stream failure occurred.

It also records:

Whether the failure qualifies for automatic refunds.

It does not directly loop through tickets and issue refunds.

---

# 27. The 25% Automatic Refund Rule

The assessment requires automatic full refunds when the stream fails before 25% of the scheduled event duration has elapsed.

The threshold is calculated from the scheduled duration.

For example:

A 120-minute event has a 30-minute threshold.

Therefore:

-   Failure before 30 minutes: automatic refund eligible
-   Failure at exactly 30 minutes: not before the threshold
-   Failure after 30 minutes: automatic refund not eligible

The implementation uses a strict "less than" comparison.

Therefore, exactly 25% of the scheduled duration does not qualify for automatic refunds.

---

# 28. StreamFailureReported Event

After ReportStreamFailureAction successfully commits the StreamIncident, it dispatches:

StreamFailureReported

The event communicates that a stream failure has been recorded.

The event allows the application to react to the incident without placing all post-incident work inside the original HTTP request.

The event is handled by:

StreamFailureReportedListener

---

# 29. StreamFailureReportedListener

The listener is responsible for reacting to a reported stream failure.

For an automatically refundable incident, the listener dispatches:

ProcessIncidentRefundsJob

The listener can also be used for non-financial side effects such as:

-   Host notifications
-   Viewer notifications
-   Monitoring
-   Analytics
-   Operations alerts

The listener should not contain the detailed refund business logic.

The refund business logic belongs to:

IssueIncidentRefundsAction

---

# 30. ProcessIncidentRefundsJob

ProcessIncidentRefundsJob is the asynchronous execution layer for automatic incident refunds.

The Job receives the StreamIncident identifier.

When the queue worker processes the Job, it retrieves the incident and invokes:

IssueIncidentRefundsAction

The Job does not decide how refunds are calculated or how tickets are modified.

Its main responsibilities are:

-   Queue the refund operation
-   Execute it outside the HTTP request
-   Retry transient failures
-   Provide controlled backoff between attempts

The current retry configuration allows three attempts with increasing delays.

This means a temporary failure during refund processing does not immediately abandon the operation.

---

# 31. Why Refund Processing Uses a Job

Refund processing may involve multiple tickets and multiple database records.

Performing all refund processing directly inside the stream-failure HTTP request would make the request responsible for potentially large amounts of work.

Using a queued Job separates the concerns:

Stream failure request
→ record incident
→ return response

Queue worker
→ process refunds asynchronously

This also allows the refund operation to be retried if a temporary infrastructure or database failure occurs.

The important principle is:

Retries handle transient failures.

Idempotency prevents those retries from creating duplicate financial records.

---

# 32. IssueIncidentRefundsAction

IssueIncidentRefundsAction contains the actual automatic refund business logic.

It is called by ProcessIncidentRefundsJob.

The Action:

1. Locks the StreamIncident.
2. Loads the associated event.
3. Confirms that the incident is eligible for automatic refunds.
4. Finds active tickets belonging to the event.
5. Locks those ticket records.
6. Creates a refund for each eligible ticket.
7. Revokes the ticket.
8. Creates the corresponding refund reserve ledger entry.
9. Commits the transaction.

All of these state and financial changes are performed transactionally.

---

# 33. Refund Idempotency

Refund processing must be safe when the Job is retried.

The refund is identified using the ticket and stream incident.

This means the same ticket should not receive multiple refunds for the same incident.

The refund ledger entry is also created idempotently using the refund identity.

Therefore, if the Job runs twice for the same incident:

First execution:

Refund created
→ Ticket revoked
→ Refund reserve created

Second execution:

Existing refund recognized
→ No duplicate refund
→ No duplicate refund reserve

This is particularly important because queued Jobs can be retried.

---

# 34. Automatic Refund Financial Flow

For an early stream failure:

Ticket sale:

Positive financial entry

Refund reserve:

Negative financial effect against the payable balance

The resulting event balance is therefore:

Ticket sales
minus
Refund reserves

For example:

Ticket sales: 10,500,000 kobo

Refund reserves: 3,500,000 kobo

Payable balance: 7,000,000 kobo

---

# 35. Stream Failure After the Threshold

If the stream fails at or after 25% of the scheduled duration:

automatic_refunds_eligible = false

The automatic refund Job must not process the incident.

Instead, the incident enters the administrative review path.

The intended workflow is:

Stream failure
→ Calculate elapsed duration
→ Determine threshold
→ Automatic refund eligible?

If yes:

StreamFailureReported
→ StreamFailureReportedListener
→ ProcessIncidentRefundsJob
→ IssueIncidentRefundsAction

If no:

StreamFailureReported
→ administrative review workflow

The automatic refund Action explicitly protects this rule by refusing to process an incident that is not marked as automatically refundable.

---

# 36. Administrative Review

Failures occurring at or after the automatic-refund threshold require administrative review.

The current automatic refund flow does not treat these incidents as automatically refundable.

The administrative review workflow is separate from the automatic refund Job.

The intended process is:

Late stream failure
→ Incident recorded
→ Automatic refund eligibility is false
→ Administrator reviews incident
→ Administrator determines whether refunds should be approved
→ Approved refunds are processed through the appropriate refund operation

This prevents a late stream failure from being automatically refunded simply because the refund Job was dispatched or retried.

---

# 37. Complete Broadcast

Endpoint:

POST /events/{event}/broadcast/complete

Authentication:

Host JWT required.

The authenticated user must own the event.

The event must currently be live.

A successful completion changes:

live
→ completed

The server records completed_at.

Once completed, the event cannot be started again through the normal broadcast-start operation.

---

# 38. Payout

Endpoint:

POST /payouts/events/{event}/payouts

Authentication:

JWT required.

The payout operation is only available after the event has completed.

The payout amount is calculated from the financial ledger.

The intended calculation is:

Ticket sales
minus
Refund reserves

The payout must not simply sum all ledger entries because refund reserves reduce the amount payable to the host.

---

# 39. Payout Idempotency

An event should have only one payout.

The payout operation therefore finds or creates the payout associated with the event rather than creating a new payout every time the endpoint is called.

Repeated payout requests should not create duplicate payout records.

For a real external payout provider, provider-side idempotency must also be used when sending the actual payout request.

This protects against a provider timeout where the payout may have succeeded externally even though the application did not receive the response.

---

# 40. Financial Ledger

The ledger provides the event-level financial accounting record.

Important entries include:

-   ticket_sale
-   refund_reserve

Ticket sales increase the payable balance.

Refund reserves reduce the payable balance.

Example:

Ticket sale
+3,500,000 kobo

Ticket sale
+3,500,000 kobo

Refund reserve
-3,500,000 kobo

Net payable
+3,500,000 kobo

All monetary amounts are represented as integer kobo.

This avoids floating-point precision problems in financial calculations.

---

# 41. Financial Integrity

Financial state changes are performed inside database transactions.

Payment capture atomically updates:

PaymentTransaction
→ captured

Ticket
→ active

LedgerEntry
→ ticket_sale

Refund processing atomically updates:

Refund
→ created

Ticket
→ revoked

LedgerEntry
→ refund_reserve

Payout creation calculates the payable balance from committed ledger state.

This prevents partial financial operations.

For example, the system should not end up with:

Payment captured
but
Ticket still pending

or:

Refund created
but
Refund ledger entry missing

because these operations are committed together.

---

# 42. Concurrency Protection

Critical operations use row-level locking.

Important state-changing records are locked while their state is being evaluated and changed.

Examples include:

-   LiveEvent
-   PaymentTransaction
-   StreamIncident
-   Ticket

This protects against concurrent requests such as:

Two payment webhooks arriving simultaneously.

Two users attempting to change the same event state simultaneously.

A refund Job being retried while another refund operation is already processing.

The general pattern is:

Load record
→ lock record
→ validate current state
→ perform transition
→ commit

---

# 43. State Transition Protection

The application does not allow arbitrary state changes.

Event states follow:

scheduled
→ live
→ completed

Payment state follows:

pending
→ captured

Ticket state follows:

pending
→ active

A stream failure creates a separate StreamIncident rather than treating the incident as an unrestricted event-state transition.

This keeps the primary event lifecycle separate from failure and financial consequences.

---

# 44. Failure and Retry Architecture

The application distinguishes between business operations and asynchronous execution.

The key example is automatic refund processing.

ReportStreamFailureAction records the incident.

StreamFailureReported communicates that the incident exists.

StreamFailureReportedListener reacts to the event.

ProcessIncidentRefundsJob executes refund processing asynchronously and provides retry behavior.

IssueIncidentRefundsAction performs the actual refund business operation.

The architecture is therefore:

ReportStreamFailureAction
→ StreamFailureReported
→ StreamFailureReportedListener
→ ProcessIncidentRefundsJob
→ IssueIncidentRefundsAction

The Action contains the business rules.

The Job provides asynchronous execution and retry behavior.

The Listener connects the event to the asynchronous operation.

---

# 45. Why the Job and Action Are Separate

The Job and Action serve different purposes.

IssueIncidentRefundsAction answers:

How should this incident's refunds be processed?

ProcessIncidentRefundsJob answers:

When and how should that refund operation be executed asynchronously and retried?

Keeping them separate makes the refund logic reusable and testable without coupling the business operation to queue system.

It also makes retry behavior explicit.

---

# 46. Retry and Idempotency Strategy

The system follows an important financial-processing principle:

Retries handle transient failures.

Idempotency prevents retries from creating duplicate state.

For example:

A refund Job may fail after processing some database operations.

The Job may subsequently retry.

The refund Action must recognize already-created refunds and ledger entries rather than creating additional ones.

The same principle applies to payment webhooks.

A provider may send the same webhook multiple times.

The payment capture Action recognizes that the payment is already captured and avoids creating another ticket sale.

---

# 47. End-to-End Event Flow

The complete business workflow is:

Signup
→ Login
→ Host creates event
→ Event is scheduled
→ Host assigns crew
→ Crew assignment is pending
→ Assigned crew member confirms availability
→ Assignment becomes accepted
→ Host starts broadcast
→ Event becomes live
→ Viewer purchases ticket
→ Payment transaction is pending
→ Payment provider sends webhook
→ Payment becomes captured
→ Ticket becomes active
→ Ticket sale is recorded in the ledger
→ Stream continues
→ Event completes
→ Event becomes completed
→ Host payout is calculated from sales less refund reserves

If the stream fails before the 25% threshold:

Stream failure
→ StreamIncident created
→ automatic_refunds_eligible is true
→ StreamFailureReported event
→ StreamFailureReportedListener
→ ProcessIncidentRefundsJob
→ IssueIncidentRefundsAction
→ Refund created
→ Ticket revoked
→ Refund reserve recorded

If the stream fails at or after the threshold:

Stream failure
→ StreamIncident created
→ automatic_refunds_eligible is false
→ Administrative review required

---

# 48. Fresh Database Test Flow

After resetting the database, the recommended test sequence is:

1. Run the migrations and seeders.
2. Obtain the seeded host credentials.
3. Login as the default host.
4. Create a viewer account.
5. Create a crew account.
6. Login as the viewer and crew users.
7. Host creates an event.
8. Host assigns the crew member.
9. Crew member accepts the assignment.
10. Verify the assignment is accepted.
11. Host starts the broadcast.
12. Verify the event is live and started_at has been recorded.
13. Viewer purchases a ticket.
14. Verify the ticket and payment are pending.
15. Send the payment webhook.
16. Verify the payment is captured.
17. Verify the ticket is active.
18. Verify a ticket_sale ledger entry exists.
19. Repeat the payment webhook.
20. Verify no duplicate ticket_sale ledger entry exists.
21. Report an early stream failure.
22. Verify the StreamIncident is automatically refundable.
23. Verify StreamFailureReported is dispatched.
24. Verify the listener dispatches ProcessIncidentRefundsJob.
25. Verify the queue worker processes the Job.
26. Verify a Refund is created.
27. Verify the ticket is revoked.
28. Verify a refund_reserve ledger entry exists.
29. Dispatch or retry the same refund Job again.
30. Verify no duplicate refund or refund reserve is created.
31. Complete the event in a separate successful-event test.
32. Create the payout.
33. Verify the payout uses ticket sales less refund reserves.
34. Repeat the payout request.
35. Verify that a second payout is not created.

---

# 49. Testing the 25% Boundary

The automatic refund rule should be tested independently from the normal happy path.

For a 120-minute event:

25% equals 30 minutes.

Test an incident before 30 minutes.

Expected:

automatic_refunds_eligible = true

The refund Job should be dispatched.

Then test an incident at exactly 30 minutes.

Expected:

automatic_refunds_eligible = false

The automatic refund Job should not process the incident.

Finally, test an incident after 30 minutes.

Expected:

automatic_refunds_eligible = false

The incident should require administrative review.

This boundary test is important because the application uses a strict "less than" comparison.

---

# 50. Testing the Crew Authorization

The crew authorization should also be tested.

Host assigning crew:

The authenticated user must be a host and must own the event.

Crew accepting assignment:

The authenticated user must be the crew member associated with the assignment.

If the host attempts to call the crew acceptance endpoint, the request should be rejected because the ConfirmCrewAvailabilityRequest expects the authenticated user to match the assignment's crew_member_id.

If Crew A attempts to accept Crew B's assignment, the request should also be rejected.

This prevents users from confirming assignments that do not belong to them.

---

# 51. Testing Broadcast Authorization

Starting a broadcast requires:

-   Authenticated user
-   Host role
-   Ownership of the event
-   Event currently scheduled
-   Accepted crew assignment

A host cannot start another host's event.

A viewer cannot start an event.

A crew member cannot start the event unless the application's authorization rules explicitly grant that capability.

An event without an accepted crew assignment cannot become live.

---

# 52. Testing Refund Idempotency

Refund idempotency should be tested by processing the same incident more than once.

The first execution creates:

-   One refund
-   One ticket revocation
-   One refund reserve ledger entry

The second execution must not create:

-   Another refund
-   Another refund reserve
-   Another ticket state transition

The final financial state must remain unchanged.

This demonstrates that the queued refund operation is safe to retry.

---

# 53. Testing Payment Idempotency

Send the same payment webhook more than once.

The first webhook should:

-   Capture the payment
-   Activate the ticket
-   Create the ticket sale ledger entry

The second webhook should recognize that the payment has already been captured.

It must not create another ticket sale.

This demonstrates safe webhook retry handling.

---

# 54. Testing Ticket Purchase Idempotency

Submit the same ticket purchase request more than once using the same Idempotency-Key.

The first request creates the ticket.

The second request returns the existing ticket associated with that Idempotency-Key.

The database should contain only one ticket for that logical purchase.

A new purchase operation should use a different Idempotency-Key.

---

# 55. Postman Environment

A useful Postman environment should contain:

-   base_url
-   host_token
-   viewer_token
-   crew_token
-   event_id
-   assignment_id
-   ticket_id
-   payment_reference
-   provider_event_id

The host token is obtained from the seeded development host.

The viewer and crew tokens are obtained after creating and logging in those users.

IDs generated during each step should be saved into the Postman environment so subsequent requests can use them.

---

# 56. Route Reference

Authentication:

POST /auth/signup
POST /auth/login
POST /auth/logout

Crew:

POST /crew/events/{event}/crew-assignments
POST /crew/assignments/{assignment}/accept

Events:

POST /events/host
POST /events/{event}/broadcast/live
POST /events/{event}/broadcast/complete

Tickets:

POST /tickets/events/{event}/tickets

Payments:

POST /payments/webhook

Stream incidents:

POST /stream/events/{event}/stream-incidents

Payouts:

POST /payouts/events/{event}/payouts

The application intentionally does not use an /api prefix.

---

# 57. Architecture

The application follows a simple layered architecture:

FormRequest
→ Controller
→ Service
→ Action
→ Model / Database

The responsibilities are:

FormRequest:

-   Request validation
-   Request authorization
-   JSON:API request normalization

Controller:

-   Receive HTTP request
-   Pass validated data to the Service
-   Return the API response

Service:

-   Orchestrate the application operation
-   Call the appropriate Action

Action:

-   Business logic
-   Database transactions
-   State transitions
-   Row locking
-   Financial integrity
-   Idempotency

Model:

-   Persistence
-   Relationships
-   Casts
-   Model-level behavior

Events and listeners are used for post-operation side effects.

Jobs are used for asynchronous and retryable operations.

---

# 58. Action Responsibilities

The principal Actions have focused responsibilities.

CreateLiveEventAction:

Creates a new scheduled event.

AcceptCrewAssignmentAction:

Changes a pending crew assignment to accepted.

BeginLiveEventAction:

Validates the event's live-start requirements and changes the event from scheduled to live.

CapturePaymentAction:

Captures a payment, activates the ticket, and records the ticket sale.

PurchaseTicketAction:

Handles ticket reservation, payment transaction creation, and purchase idempotency.

ReportStreamFailureAction:

Records a stream failure and determines automatic refund eligibility.

IssueIncidentRefundsAction:

Processes automatic refunds for an eligible stream incident.

CreatePayoutAction:

Calculates and creates the event payout from the financial ledger.

---

# 59. Event and Listener Responsibilities

The main event-driven refund flow is:

StreamFailureReported

This event represents a successfully recorded stream failure.

StreamFailureReportedListener

This listener reacts to the incident.

If automatic refunds are allowed, it dispatches ProcessIncidentRefundsJob.

The listener may also trigger non-financial side effects such as notifications, monitoring, or analytics.

The listener should not perform the detailed refund loop itself.

---

# 60. Job Responsibilities

ProcessIncidentRefundsJob is the single Job responsible for automatic incident refund processing.

There is no need for separate Jobs such as:

ProcessAutomaticIncidentRefundsJob

and:

ProcessIncidentRefundsJob

The single Job is sufficient.

The Job:

-   Receives the incident ID
-   Runs asynchronously
-   Invokes IssueIncidentRefundsAction
-   Retries transient failures
-   Uses backoff between attempts

The refund business rules remain in IssueIncidentRefundsAction.

---

# 61. Business Rules

The primary business rules are:

1. New events start in scheduled.
2. Only the event host can control their event.
3. A host can assign production crew to their event.
4. The assigned crew member confirms their availability.
5. An event requires an accepted crew assignment before going live.
6. Only scheduled events can transition to live.
7. Only live events can transition to completed.
8. Tickets can be purchased while the event is scheduled or live.
9. Ticket purchase creates a pending payment transaction.
10. Ticket access becomes active only after successful payment confirmation.
11. Payment webhooks are idempotent.
12. Ticket purchases are idempotent through Idempotency-Key.
13. Payment capture and ticket activation occur atomically.
14. Every successful ticket sale creates a ticket_sale ledger entry.
15. Stream failures are recorded as StreamIncidents.
16. Stream failure before 25% of scheduled duration is eligible for automatic full refunds.
17. Stream failure at or after 25% requires administrative review.
18. Automatic refunds are processed asynchronously through a queued Job.
19. Refund processing is retryable.
20. Refund processing is idempotent.
21. Refunds are associated with both the ticket and stream incident.
22. A refund reserve reduces the event's payable balance.
23. Payouts can only be generated for completed events.
24. An event must not receive multiple payout records.
25. Payout amount is ticket sales less refund reserves.
26. Monetary amounts are stored as integer kobo.
27. Critical state transitions use database transactions.
28. Critical concurrent operations use row-level locking.
29. External payment identifiers are treated as provider-controlled identifiers.
30. Production webhooks must verify provider signatures.

---

# 62. Failure and Idempotency Summary

The system protects important operations using several complementary mechanisms.

Ticket purchase:

Idempotency-Key
→ prevents duplicate logical purchases

Payment webhook:

Payment state + provider event ID + transaction
→ prevents duplicate payment capture

Refund processing:

Ticket + StreamIncident identity + transaction
→ prevents duplicate refunds

Payout:

One payout per event
→ prevents duplicate payout records

Concurrency:

Row locking + database transactions
→ prevents conflicting simultaneous state transitions

Queue failures:

Job retries + idempotent Action
→ allows transient failures to recover without duplicating financial state

---

# 63. Financial Flow Summary

A successful ticket purchase eventually produces:

PaymentTransaction
→ captured

Ticket
→ active

LedgerEntry
→ ticket_sale

An automatically refundable stream failure produces:

Refund
→ created

Ticket
→ revoked

LedgerEntry
→ refund_reserve

The host's eventual payout is based on:

Ticket sales
minus
Refund reserves

This keeps the payout calculation based on recorded financial events rather than reconstructing financial state from mutable ticket or event data.

---

# 64. Production Considerations

Before production deployment, the following should be added or verified:

-   Payment provider webhook signature verification
-   Provider API verification where required
-   Database unique constraints for business identifiers
-   Queue configuration
-   Job retry and failure monitoring
-   Structured logging
-   Monitoring and alerting
-   Administrative refund-review endpoints
-   Authorization policies for administrative operations
-   HTTPS
-   Secure JWT configuration
-   Database backups
-   Redis configuration
-   Authentication rate limiting
-   Webhook rate limiting where appropriate
-   Production secret management
-   External payout provider idempotency
-   Failed-job monitoring

The payment webhook must never trust arbitrary client-provided payment events in production without authenticating the payment provider.

---

# 65. Troubleshooting

## "An accepted crew assignment is required."

The event cannot go live until a crew assignment has been accepted.

Verify that:

-   The crew assignment exists.
-   The correct crew member was assigned.
-   The assigned crew member has authenticated.
-   The assigned crew member called the acceptance endpoint.
-   The assignment status is accepted.

The host does not accept the crew assignment. The assigned crew member does.

---

## "403 Forbidden" when accepting a crew assignment

The acceptance request is authorized by ConfirmCrewAvailabilityRequest.

The authenticated user must match the assignment's crew_member_id.

If the request is made using the host JWT, it will be rejected.

Use the JWT belonging to the crew member assigned to that specific assignment.

---

## "Tickets are not available for this event."

The event must currently be scheduled or live.

Check the event's status.

---

## "Idempotency-Key is required."

The ticket purchase request must contain an Idempotency-Key HTTP header.

The key belongs in the request header rather than the JSON:API attributes.

---

## Payment webhook returns 404.

Verify that:

-   The payment webhook route exists.
-   The provider reference exists in PaymentTransaction.
-   The webhook provider reference exactly matches the reference created during ticket reservation.
-   The payment transaction belongs to an existing ticket.

The webhook is designed to confirm an existing payment transaction.

---

## Payment remains pending.

Check whether:

-   The webhook was received.
-   The provider reference matches.
-   The provider event ID is valid.
-   The payment transaction exists.
-   The capture transaction completed successfully.
-   The application logs contain an exception.

---

## Ticket remains pending.

A ticket becomes active only after successful payment capture.

Check the associated PaymentTransaction.

---

## No refund was created after a stream failure.

Check:

1. The event was live when the failure was reported.
2. The incident was created.
3. automatic_refunds_eligible is true.
4. The queue worker is running.
5. StreamFailureReported was handled.
6. ProcessIncidentRefundsJob was processed.
7. The incident contains active tickets eligible for refund.
8. The Job did not fail and enter the failed-jobs table.

---

## Refund Job runs but creates duplicates.

Check that refund processing identifies refunds using the ticket and stream incident relationship and that the corresponding database uniqueness constraints are in place.

The refund Action must remain idempotent because the Job can be retried.

---

## "No payable balance exists."

Check the event's ledger.

The event should have ticket_sale entries if payments were successfully captured.

Refund reserves reduce the payable amount.

If total refund reserves equal or exceed ticket sales, there is no positive payout balance.

A missing ticket_sale ledger entry usually indicates that the payment capture workflow did not complete successfully.

---

# 66. Useful Development Verification

Useful records to inspect include:

-   User
-   LiveEvent
-   CrewAssignment
-   Ticket
-   PaymentTransaction
-   StreamIncident
-   Refund
-   LedgerEntry
-   Payout

For a successful payment flow, the expected relationship is:

PaymentTransaction captured
→ Ticket active
→ Ledger ticket_sale

For an early stream failure:

StreamIncident automatically refundable
→ Refund created
→ Ticket revoked
→ Ledger refund_reserve

For a payout:

Event completed
→ Sales calculated
→ Refund reserves deducted
→ Payout created

---

# 67. Assessment Focus

The implementation demonstrates the following assessment requirements.

## State Management

The event lifecycle is:

scheduled
→ live
→ completed

State transitions are protected by business rules and database transactions.

## Payment Processing

Payment transactions begin as pending and become captured only after payment confirmation.

## Ticket Access

Tickets begin as pending and become active only after successful payment capture.

## Financial Integrity

Successful payments create ticket sale ledger entries.

Refunds create refund reserve ledger entries.

Payouts are calculated from the financial ledger.

## Stream Failure Handling

Failures before 25% qualify for automatic full refunds.

Failures at or after 25% require administrative review.

## Asynchronous Processing

Automatic refunds are processed through a queued Job.

## Retry Handling

The refund Job retries transient failures.

## Idempotency

The application protects:

-   Ticket purchases
-   Payment webhooks
-   Refund processing
-   Payout creation

against duplicate operations.

## Concurrency Protection

Database transactions and row locking protect critical state transitions and financial operations.

---

# 68. Final Architecture Summary

The overall application flow is:

HTTP Request
→ FormRequest
→ Controller
→ Service
→ Action
→ Database

For event-driven side effects:

Action
→ Event
→ Listener
→ Job
→ Action

The refund workflow is specifically:

ReportStreamFailureAction
→ StreamFailureReported
→ StreamFailureReportedListener
→ ProcessIncidentRefundsJob
→ IssueIncidentRefundsAction
→ Refund + Ticket Revocation + Refund Ledger

The important architectural distinction is:

ReportStreamFailureAction records the incident and determines eligibility.

StreamFailureReported communicates that the incident occurred.

StreamFailureReportedListener reacts to the incident and dispatches asynchronous work.

ProcessIncidentRefundsJob provides asynchronous execution and retry behavior.

IssueIncidentRefundsAction performs the actual refund business operation.

This separation keeps the HTTP request fast, keeps business logic inside Actions, and makes the financial operation safe to retry.
