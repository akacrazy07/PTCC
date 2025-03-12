CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    quantidade_vendida INT UNSIGNED NOT NULL,
    data_venda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    preco_unitario_venda DECIMAL (10, 2) NOT NULL,
    INDEX idx_produto_id (produto_id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);