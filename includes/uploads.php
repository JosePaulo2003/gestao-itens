<?php

declare(strict_types=1);

/*
 * Funcoes de upload de imagem.
 *
 * Todas as imagens do sistema passam por esta validacao antes de serem
 * salvas em /uploads. Isso evita duplicar regra em itens e usuarios.
 */

const IMAGE_MAX_BYTES = 10485760; // 10 MB.

const IMAGE_EXTENSIONS = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

function save_uploaded_image(array $file, string $folder, string $prefix): ?string
{
    // Campo opcional: se nenhum arquivo foi enviado, o registro segue sem foto.
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // Qualquer erro do PHP no upload deve virar uma mensagem amigavel.
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Nao foi possivel enviar a imagem.');
    }

    if (($file['size'] ?? 0) > IMAGE_MAX_BYTES) {
        throw new RuntimeException('A imagem deve ter no maximo 10 MB.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');

    if (!is_uploaded_file($tmpName)) {
        throw new RuntimeException('Arquivo de imagem invalido.');
    }

    $mimeType = mime_content_type($tmpName);

    // A extensão final vem do MIME real do arquivo, não do nome enviado pelo usuário.
    if (!isset(IMAGE_EXTENSIONS[$mimeType])) {
        throw new RuntimeException('Envie uma foto em JPG, PNG, WEBP ou GIF.');
    }

    $uploadDir = __DIR__ . '/../uploads/' . $folder;

    // Cria a pasta automaticamente caso ela ainda nao exista no XAMPP.
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    // Nome unico para evitar sobrescrever imagens de outros cadastros.
    $fileName = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . IMAGE_EXTENSIONS[$mimeType];
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Nao foi possivel salvar a imagem.');
    }

    return url_for('/uploads/' . $folder . '/' . $fileName);
}

function save_item_image(array $file, string $sector): ?string
{
    // Imagens de itens ficam agrupadas em /uploads/items.
    return save_uploaded_image($file, 'items', $sector);
}

function save_user_photo(array $file, string $sector): ?string
{
    // Fotos de usuários usam prefixo diferente para facilitar auditoria do diretório.
    return save_uploaded_image($file, 'users', $sector . '-user');
}
