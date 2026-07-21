-- Campos usados pelos documentos editaveis do almoxarifado.
ALTER TABLE items
    ADD COLUMN brand_model VARCHAR(180) NULL AFTER description,
    ADD COLUMN patrimony_number VARCHAR(80) NULL AFTER brand_model,
    ADD COLUMN serial_number VARCHAR(80) NULL AFTER patrimony_number,
    ADD COLUMN other_materials TEXT NULL AFTER serial_number;
