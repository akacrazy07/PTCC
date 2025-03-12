CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_produto VARCHAR(100) NOT NULL,
    descricao TEXT,
    quantidade INT UNSIGNED NOT NULL DEFAULT 0,
    preco DECIMAL(10,2) NOT NULL,
    imagem VARCHAR(255) COMMENT 'Nome do arquivo da imagem'
);