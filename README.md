# Integração UNO ERP com API CNPJá.

O objetivo desse script é fazer uma verificação diária toda manhã de pedidos aprovados no dia anterior no sistema UNO ERP utilizando os serviços da API CNPJá.

Requisitos:
1. Apache server.
2. Agendador de tarefas ou CRONTAB.
3. Configurar a função 'mail' no php.ini com um e-mail válido, que será utilizando como remetente no disparo dos e-mails.
4. Configurar o script para rodar todo dia de manha ou de madrugada (após 00:00) pois o script busca pedidos aprovados no dia anterior.

Mecânica do script:

1. Busca por pedidos aprovados no dia anterior no UNO ERP
2. Através do CNPJ dos clientes desses pedidos aprovados, é feito uma busca dos dados na Receita Federal e Sintegra através da API CNPJá.
3. Os dados dessa busca são comparados com os dados cadastrais do cliente no UNO ERP.
4. Caso houver divergência cadastral (como por exemplo inscrição estadual inativa) é feita uma compilação.
5. Essas informações compiladas são enviadas para os e-mails informados no script.
6. O email enviado é separado por pedido, informado os dados do cliente e quais foram as divergências encontradas.




