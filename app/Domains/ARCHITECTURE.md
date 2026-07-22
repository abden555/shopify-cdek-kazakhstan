# Domain architecture

The application uses Laravel-friendly, DDD-inspired boundaries. Each domain owns its use-case orchestration (`Actions`), application services (`Services`), persistence abstractions (`Repositories`), immutable input/output objects (`DTOs`), integration contracts (`Interfaces`), domain failures (`Exceptions`), and business notifications (`Events`).

`Models` remain Laravel persistence models during the incremental migration. Controllers and jobs should depend on domain actions or interfaces, never directly on carrier implementations.

Carrier integrations are intentionally the only executable adapter skeleton at this stage. `CdekCarrier` implements the full `CarrierInterface` and throws an explicit exception until its API integration is implemented.
