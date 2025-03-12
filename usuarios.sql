CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL
);

INSERT INTO usuarios (usuario, senha) VALUES 
    ('admin', '$2y$10$cLZegIJV.SUQdyF.5LFgO.q5NzB5gMTxcC7o0MCYS4i/ISZjNowYG'),
    ('funcionario1', '$2y$10$IQ1UBzls1GrRefl1Nk78hOerY9L/2geNUqx5Se7scPCd8YGC3RzlO'),
    ('teste', '$2y$10$Afbc8GzLtGlWQCW5pMZSn.mBzTppuBdwj7uyJYwVeGJSd5QLGKsES');