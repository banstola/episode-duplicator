
![Architecture Diagram](./architecture.png)
Architecture Diagram available at [Mermaid Live Editor](https://mermaid.ai/d/f0e315af-ba33-46f7-b6e4-0151138862ea)

![Duplication flow](./duplicate_flow.png)
Duplicate Episode workflow diagram available at [Mermaid Live Editor](https://mermaid.ai/d/1524b44a-cc98-4011-adb1-9878ce025741):


## Services 
1. postgresql - Database
2. redis - Used directly for Locking and Status storage
3. horizon - Supervisor for workers
4. nginx - Reverse proxy
5. laravel - PHP application

## Database Schema - generated using PHPStorm
![Database Schema](./entity_relationship.png)
