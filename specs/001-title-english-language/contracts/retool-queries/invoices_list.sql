-- invoices_list.sql
-- Parameters: {{limit}} (default 100), {{offset}} (default 0)
SELECT id, invoice_number, total, status, issued_date, due_date
FROM invoices
ORDER BY issued_date DESC
LIMIT {{limit}} OFFSET {{offset}};
