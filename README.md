
# Sistema de estoque e vendas 

O seguinte projeto, foi feito para um trabalho de conclusão de curso (TCC), para a Escola Técnica de Brazlândia (ETBRAZ). Usa como referência, a Panificadora Viola, de Brazlândia. Foi feito uma pesquisa de campo, para saber o que a panificadora precisaria em um sistema de estoque e vendas.

É um sistema simples e focado na gestão interna de estoque e planejamento de produção, feito em HTML, CSS e Bootstrap (como framework), PHP para Backend e SQL para banco de dados (usando mysqli como extensão do php para o banco de dados)


## Autores

- [@akacrazy07](https://www.github.com/akacrazy07)

## Funcionalidades
- **Controle de Estoque**: Monitoramento em tempo real de insumos e produtos acabados.
- **Registro de Vendas**: Cadastro e rastreamento de vendas internas.
- **Gerenciamento de Pedidos**: Organização e acompanhamento de pedidos da produção.
- **Planejamento de Produção**: Ferramenta para planejar a produção com base em demanda e estoque.
- **Gerenciamento de Usuários**: Cadastro e logs para ver o que cada usuário faz dentro do sistema
## Pré-requisitos
- **Servidor web** (ex.: XAMPP, LocalWP, Laragon)
- **PHP 7.4 ou superior**
- **MySQL** (ou outro banco de dados, desde que seja **INNOdb**)
- **Navegador web moderno** (ex.: Chrome, Firefox)

## Instalação
1. Clone o repositório:
   ```bash
   git clone https://github.com/akacrazy07/PTCC
2. Mova a pasta do projeto para o diretório do servidor web (**ex.: C:\xampp\htdocs**).
3. Configure o banco de dados
- Importe o arquivo *panificadora_db.sql* (ele é Self-Contained, e já vem com a criação do esquema, não tem necessidade de configurar nada, apenas importar)
4. Atualize as configurações de conexão no arquivo *config.php* com suas credenciais do banco.
5. Acesse o sistema em http://localhost/PTCC no navegador.
## Uso
1. Acesse o sistema pelo navegador em `http://localhost/PTCC`.
2. Faça login com as credenciais de administrador (Nome: Admin; CPF: 123.456.789.09; Senha: 12345) (Se tiver uma habilidade básica com banco de dados, pode puxar a tabela de usuários para ver a credencial de outros usuários, como o de gerente e vendedor).
3. Navegue pelo painel para:
- Adicionar ou remover itens do estoque
- Gerenciar usuários
- Gerenciar fornecedores
- Dentre várias outras funções !
## Contribuição
Este é um projeto de TCC, mas contribuições são bem-vindas! Para contribuir:
1. Faça um fork do repositório.
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`).
3. Commit suas alterações (`git commit -m 'Adiciona nova funcionalidade'`).
4. Envie para o repositório remoto (`git push origin feature/nova-funcionalidade`).
5. Abra um Pull Request.
## Licença

[GNU](https://github.com/akacrazy07/PTCC/blob/main/LICENSE)

