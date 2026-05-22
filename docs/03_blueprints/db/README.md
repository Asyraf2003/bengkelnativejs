# db

Blueprints and workflows for database hardening, audit timestamps, and MySQL-to-PostgreSQL migration readiness.

## Files

| File | Type | Contents |
|---|---|---|
| `0001_temporal_audit_columns_blueprint.md` | Blueprint | Temporal and audit column design for domain tables |
| `0002_mysql_postgresql_crud_readiness_blueprint.md` | Blueprint | Database CRUD readiness for compatibility with MySQL and PostgreSQL |
| `0003_db_hardening_workflow.md` | Workflow | Database hardening execution order and required proof |
| `0004_db_audit_matrix.md` | Matrix | Audit matrix for tables, timestamps, immutability, and migration risk |
| `0005_notes_timestamp_patch_blueprint.md` | Blueprint | Timestamp patch for notes tables |
| `0006_customer_payment_refund_timestamp_patch_blueprint.md` | Blueprint | Timestamp patch for customer payment and refund tables |
| `0007_allocation_tables_timestamp_immutability_patch_blueprint.md` | Blueprint | Timestamp and immutability patch for allocation tables |
| `0008_supplier_procurement_timestamp_hardening_patch_blueprint.md` | Blueprint | Timestamp hardening patch for supplier and procurement tables |
| `0009_inventory_movement_timestamp_readiness_hardening_patch_blueprint.md` | Blueprint | Timestamp hardening patch for inventory movement |
| `0010_inventory_projection_timestamp_policy_blueprint.md` | Blueprint | Timestamp policy for inventory projection |
| `0011_work_item_timestamp_readiness_hardening_patch_blueprint.md` | Blueprint | Timestamp hardening patch for work items |

## Note

This folder only defines database blueprints and workflows.

Runtime database changes must still be proven through migration diffs, targeted tests, audit commands, and local verification output.
