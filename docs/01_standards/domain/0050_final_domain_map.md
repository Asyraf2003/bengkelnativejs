# P0 - Final Domain Map

## Purpose
Lock the final domain map for the cashier project so the AI does not mix terms, sources of truth, or business boundaries.

## Final Domain Map
- `products` = item master
- `product_inventory` + `inventory_movements` = stock source of truth
- `supplier_invoices` + items = stock entry point and the basis for avg_cost / COGS
- `customer_orders` = Customer Notes
- `customer_transactions` = Cases
- `customer_transaction_lines` = Details

## Mandatory Rule
- Use the final domain terms consistently.
- Do not mix final terms with old temporary terms.
- Do not move the source of truth away from the final map without an explicit decision.

## Reporting Reminder
- Reports read the final domain.
- Reports are not a new source of truth.
