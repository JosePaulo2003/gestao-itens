-- Ajusta o texto herdado da planilha para aparecer de forma mais limpa na descricao dos itens.
-- Os textos abaixo usam hex UTF-8 para preservar "Codigo" com acento em qualquer terminal/editor.
UPDATE items
SET description = REPLACE(
    description,
    CONVERT(0x43C3B36469676F20457863656C3A USING utf8mb4) COLLATE utf8mb4_unicode_ci,
    CONVERT(0x43C3B36469676F3A USING utf8mb4) COLLATE utf8mb4_unicode_ci
)
WHERE description LIKE CONCAT(
    '%',
    CONVERT(0x43C3B36469676F20457863656C3A USING utf8mb4) COLLATE utf8mb4_unicode_ci,
    '%'
);
