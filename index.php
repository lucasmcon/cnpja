<?php

include('services/cnpja.php');
include('services/vd_pedido.php');

$cnpja = new cnpja();
$vd_pedido = new vd_pedido();


$Rs = $vd_pedido->buscaPedidosAprovados();// Busca de pedidos aprovados no UNO no dia anterior

if ($Rs != '') {//Caso retorne resultados na busca por pedidos aprovados

    $rows = count($Rs);
    $ind_dispara_email = 0; //Variavel auxiliar para verificar se dispara o e-mail

    //Emails que irão receber os avisos de divergências cadastrais nos pedidos, para mais de 1 e-mail, separar por virgulas
    $to = 'exemplo@exemplo.com.br, exemplo2@exemplo.com.br';
    //Cabeçalho com informações de remetente e formatação HTML para corpo do e-mail
    $headers = "Content-Type: text/html; charset=ISO-8859-1\r\n";
    $headers .= 'From: aviso@exemplo.com.br'; //informar remetente - a função mail deve estar devidamente configurada no php.ini
    //Assunto do e-mail
    $subject = 'UNO - Pedidos aprovados: Aviso de situacao cadastral';

    //Corpo inicial do aviso em caso de divergência, será complementado no loop 'for'
    $body = "<b>AVISO</b>: Os pedidos abaixo foram aprovados no UNO em ' . $Rs[0]['dt_aprovacao'] . ' e possuem divergencias com a Receita/SINTEGRA.<br><br>";
    $body .= "Realizar analise cadastral no UNO para evitar erros no faturamento.<br><br>";

    for ($i = 0; $i < $rows; $i++) {

        $Rs_CNPJ = $cnpja->consultaCNPJ($Rs[$i]['cnpj']); //Consulta utilizando serviço da API, com CNPJ dos pedidos do UNO aprovados no dia anterior

        $decoded = json_decode($Rs_CNPJ); //Decodificação do JSON para objeto.

        //Tratamento de erro com 'break' para quebrar o loop e encerrar script.  
        if (property_exists($decoded, "error")) {

            $error_body = "<b>AVISO</b>: API CNPJa retornou erro, o script nao foi executado corretamente.</b><br><br>";
            $error_body .= "<b>ERRO:</b> " . $decoded->error . " - " . $decoded->message;

            mail($to, $subject, $error_body, $headers);
            break; //quebra o loop for.

        } else {

            $caracteres_ie = array(".", ",", "-", "/");

            $ie_sintegra = $decoded->sintegra->home_state_registration != null ? (int) $decoded->sintegra->home_state_registration : 'ISENTO';
            $ie_uno = str_replace($caracteres_ie, "", $Rs[$i]['insc_estadual']);

            $crt_uno = $Rs[$i]['crt'];
            $crt = $decoded->simples_nacional->simples_optant == true ? '1' : '3';

            $situacao_receita = $decoded->registration->status;
            $simples_nacional = $decoded->simples_nacional->simples_optant == true ? 'SIM' : 'NAO';
            $simei = $decoded->simples_nacional->simei_optant == true ? 'SIM' : 'NAO';
            $link_ficha_receita = $decoded->files->registration;

            $primary_activity = $decoded->primary_activity->code . ' - ' . $decoded->primary_activity->description;


            if ($ie_sintegra != $ie_uno || $situacao_receita != 'ATIVA' || $crt_uno != $crt) {

                $ind_dispara_email++;

                $body .= "<b>Pedido UNO</b>: " . $Rs[$i]['cod_pedido'] . "\n<br>";
                $body .= "<b>Data de aprov</b>: " . $Rs[$i]['dt_aprovacao'] . "\n\n<br>";
                $body .= "<b>Cliente</b>: " . utf8_decode($Rs[$i]['cod_cliente'] . ' - ' . $Rs[$i]['nome_cliente']) . "\n<br>";
                $body .= "<b>CNPJ</b>: " . $Rs[$i]['cnpj'] . "\n<br><br>";
                $body .= "Situacao do CNPJ na receita federal: <b>" . $situacao_receita . "\n</b><br>";
                $body .= "<b>IE informada no UNO</b>: " . $Rs[$i]['insc_estadual'] . "\n<br>";
                $body .= "<b>IE ativa no SINTEGRA</b>: " . $ie_sintegra . "\n\n<br><br>";
                $body .= "Optante Simples Nacional na receita: " . $simples_nacional . "\n<br>";

                if ($crt_uno == 1) {
                    $body .= '<b>Optante Simples nacional no UNO: SIM</b>';
                } else if ($crt_uno == 3) {
                    $body .= '<b>Optante Simples nacional no UNO: NAO</b>';
                } else {
                    $body .= '<b>Optante Simples nacional no UNO: NAO INFORMADO NO SISTEMA</b>';
                }

                $body .= "<br>Optante SIMEI: " . $simei . "\n<br><br>";

                $body .= "Atividade primaria: " . utf8_decode($primary_activity) . "\n<br>";

                if (property_exists($decoded, 'secondary_activities')) {

                    $qtd_secondary = count($decoded->secondary_activities);

                    $body .= "<dl>";
                    $body .= "<dt>Atividades secundarias:</dt>";

                    for ($j = 0; $j < $qtd_secondary; $j++) {

                        $body .= "<li>" . utf8_decode($decoded->secondary_activities[$j]->code . ' - ' . $decoded->secondary_activities[$j]->description) . "</li>";
                    }
                    $body .= "</dl>";
                }

                $body .= "<br><br><a href='" . $link_ficha_receita . "'>Clique aqui</a> para acessar a ficha cadastral da Receita Federal<br><br><hr><br>";
            }
        }
    }

    if ($ind_dispara_email >= 1) {//Verificação com variável auxiliar para disparar e-mail com as inconsistencias encontradas
        mail($to, $subject, $body, $headers);
    } else if(!property_exists($decoded, "error")) {//Caso nenhuma inconsistencia seja encontrada e não tenha retornado erro, envia um aviso.
        $body = 'Script executado com sucesso. Nenhuma divergencia cadastral identificada.';
        mail($to, $subject, $body, $headers);
    }
} else {//Caso não retorne nada na busca por pedidos aprovados, dispara um aviso.

    $to = 'exemplo@exemplo.com.br';
    $headers = "Content-Type: text/html; charset=ISO-8859-1\r\n";
    $headers .= 'From: aviso@exemplo.com.br';
    $subject = 'UNO - Pedidos aprovados: Aviso de situacao cadastral';
    $body = 'Script executado com sucesso. Nenhum resultado na busca por pedidos aprovados em ' . date("d-m-Y", strtotime("-1 days"));

    mail($to, $subject, $body, $headers);
}
