# Integração [UNO ERP](https://www.unosolucoes.com.br/) com [API CNPJá](https://www.cnpja.com.br/).

O objetivo desse script é fazer uma verificação diária de pedidos aprovados no dia anterior no sistema UNO ERP utilizando os serviços da API CNPJá.

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


#Configuração

## Variáveis de sessão

Editar o arquivo database.ini localizado em services/private/ com os dados de acesso ao MySQL Server

```ini
DB_Serv = <server_address>
DB_User = <user>
DB_Pass = <passord>
DB_Port = <port>
DB_Name = <schema>
```

## Variáveis

```php
$to //E-mail destinatário, para maias de um e-mail, separar por vírgula
$from //E-mail remetente configurado no php.ini
```

## Comunicação com a API CNPJá

Editar a variável ``` $token ``` no arquivo cnpja.php localizado em services/ com a chave (token) da API.

Na variável ```$service_url``` a consulta está configurada com os parâmetros **sintegra_max_age** e **simples_max_age**, para retornar informações em tempo real.

Para mais, consultar [documentação](https://www.cnpja.com.br/docs) oficial da [API CNPJá](https://www.cnpja.com.br/) 

```php

    class cnpja{

        function consultaCNPJ($cnpj){
            
            $service_url = "https://api.cnpja.com.br/companies/".$cnpj."?sintegra_max_age=1&simples_max_age=1";
            $token = ""; //Token da conta CNPJá
            $content = "multipart/form-data";
            $headers = array('authorization:'.$token, 'Content-Type:'.$content);

            $curl = curl_init($service_url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);

            $curl_response = curl_exec($curl);
            curl_close($curl);

            return $curl_response;

        }
    }

```




