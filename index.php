<?php

include('services/cnpja.php');
include('services/vd_pedido.php');

$cnpja = new cnpja();
$vd_pedido = new vd_pedido();

$from = ''; // e-mail rementente configurado no sendmail.php e php.ini


$Rs = $vd_pedido->buscaPedidosAprovados(); // Busca de pedidos aprovados no UNO no dia anterior

if ($Rs != '') { //Caso retorne resultados na busca por pedidos aprovados

    $rows = count($Rs);
    $ind_dispara_email = 0; //Variavel auxiliar para verificar se dispara o e-mail

    //Emails que irão receber os avisos de divergências cadastrais nos pedidos
    $to = 'exemplo@exemplo.com.br, exemplo2@exemplo.com.br';
    //Cabeçalho com informações de remetente e formatação HTML para corpo do e-mail
    $headers = "Content-Type: text/html; charset=ISO-8859-1\r\n";
    $headers .= 'From: '.$from;
    //Assunto do e-mail
    $subject = 'UNO - Pedidos aprovados: Aviso de situacao cadastral';

    //Corpo inicial do aviso em caso de divergência, será complementado no loop 'for'
    $body = '<b>AVISO</b>: Os pedidos abaixo foram aprovados no UNO em ' . $Rs[0]['dt_aprovacao'] . ' e possuem divergencias com a Receita/SINTEGRA.<br><br>';
    $body .= 'Realizar analise cadastral no UNO para evitar erros no faturamento.<br><br>';
    $body .= "<b>IMPORTANTE:</b> O cliente pode possuir mais de uma inscri&ccedil;&atilde;o estudual ATIVA para a UF, a verifica&ccedil;&atilde;o &eacute; feita de acordo com a I.E principal no SINTEGRA.<br><br>";

    $body .= "<style> table, th, td{ border: 1px solid gray; border-collapse: collapse;} </style>";
    $body .= "<table>";
    $body .= "<tr bgcolor='#7998d5' style='color:white;'>";
    $body .= "<th>PEDIDO UNO</th>";
    $body .= "<th>SITUA&Ccedil;&Atilde;O</th>";
    $body .= "<th>DATA APROV.</th>";
    $body .= "<th>COD. CLIENTE</th>";
    $body .= "<th>RAZAO SOCIAL UNO</th>";
    $body .= "<th>RAZAO SOCIAL RECEITA</th>";
    $body .= "<th>CNPJ</th>";
    $body .= "<th>SITUACAO CNPJ NA RECEITA</th>";
    $body .= "<th>I.E UNO</th>";
    $body .= "<th>I.E PRINCIPAL SINTEGRA</th>";
    $body .= "<th>SIMPLES NAC. RECEITA</th>";
    $body .= "<th>SIMPLES NAC. UNO</th>";
    $body .= "<th>UF</th>";
    $body .= "<th>QUALIFICACAO</th>";
    $body .= "<th>FICHA RECEITA</th>";
    $body .= "</tr>";

    
    
    for ($i = 0; $i < $rows; $i++) {


        if($i == 59){ //a API tem um limite de 60 consultas por minuto. Sleep de 60 segundos caso retorne mais de 60 pedidos aprovados para poder reiniciar a contagem e não travar o script.
            sleep(60);
        }
        
        $Rs_CNPJ = $cnpja->consultaCNPJ($Rs[$i]['cnpj']); //Consulta utilizando serviço da API, com CNPJ dos pedidos do UNO aprovados no dia anterior

        $decoded = json_decode($Rs_CNPJ); //Decodificação do JSON para objeto.

        //Tratamento de erros com 'break' para quebrar o loop e encerrar script.  
        
        
        if(property_exists($decoded, "code")){
            
            $error_body = "<b>AVISO</b>: API CNPJa atingiu o limite de consultas por minuto.</b><br><br>";
            $error_body .= "<b>ERRO:</b> " . $decoded->code . " - " . $decoded->message;

            mail($to, $subject, $error_body, $headers);
            break;
        
        }else if (property_exists($decoded, "error")) {

            $error_body = "<b>AVISO</b>: API CNPJa retornou erro, o script nao foi executado corretamente.</b><br><br>";
            $error_body .= "<b>ERRO:</b> " . $decoded->error . " - " . $decoded->message;

            mail($to, $subject, $error_body, $headers);
            break;

        }else if($decoded->name == ''){

            $error_body = "<b>AVISO</b>: Falha de comunicação com a API, verificar status.</b><br><br>";
            
            mail($to, $subject, $error_body, $headers);
            break;
        
        } else {

            $caracteres_ie = array(".", ",", "-", "/");

            $razao_social_uno = $Rs[$i]['razao_social'];
            $razao_social_receita = $decoded->name;

            similar_text($razao_social_uno, $razao_social_receita, $percent); //Verificação das Razoes Sociais Receita x UNO - até 80% de similaridade passa. Menos que isso entra no aviso

            $ie_sintegra = $decoded->sintegra->home_state_registration != null ? (int) $decoded->sintegra->home_state_registration : 'ISENTO';
            $ie_uno = str_replace($caracteres_ie, "", $Rs[$i]['insc_estadual']);

            $crt_uno = $Rs[$i]['crt'];
            $crt = $decoded->simples_nacional->simples_optant == true ? '1' : '3';

            $situacao_receita = $decoded->registration->status;
            $simples_nacional = $decoded->simples_nacional->simples_optant == true ? '<b><font color="green">SIM</font></b>' : '<b><font color="red">NAO</font></b>';
            $link_ficha_receita = $decoded->files->registration;

            $primary_activity = $decoded->primary_activity->code . ' - ' . $decoded->primary_activity->description;


            if ($ie_sintegra != $ie_uno || $situacao_receita != 'ATIVA' || $crt_uno != $crt || $percent < 80) {

                $ind_dispara_email++;

                $bgcolor = $ind_dispara_email % 2 == 0 ? 'bgcolor="white"' : 'bgcolor="#d8d8d8"';
                $razao_social_color = $percent < 80 ? 'style="color:red"' : '';
                $ie_uno_color = $ie_sintegra != $ie_uno ? 'style="color:red"' : '';

                $body.= "<tr ".$bgcolor.">";
                $body.= "<td>".$Rs[$i]['cod_pedido']."</td>";
                $body.= "<td>".utf8_decode($Rs[$i]['situacao'])."</td>";
                $body.= "<td>".$Rs[$i]['dt_aprovacao']."</td>";
                $body.= "<td>".$Rs[$i]['cod_cliente']."</td>";
                $body.= "<td ".$razao_social_color.">".utf8_decode($razao_social_uno)."</td>";
                $body.= "<td>".$razao_social_receita."</td>";
                $body.= "<td>".$Rs[$i]['cnpj']."</td>";
                $body.= "<td>".$situacao_receita."</td>";
                $body.= "<td ".$ie_uno_color.">".$Rs[$i]['insc_estadual']."</td>";
                $body.= "<td>".$ie_sintegra."</td>";                
                $body.= "<td>".$simples_nacional."</td>";
                
                if($crt_uno == 1){
                    $body.= "<td> <b><font color='green'>SIM</font></b></td>";
                }else if ($crt_uno == 3){
                    $body.= "<td> <b><font color='red'>NAO</font></b></td>";
                }else{
                    $body.= "<td> <b><font color='red'>NAO INFORMADO NO SISTEMA</font></b></td>";
                }
                $body.= "<td>".$Rs[$i]['sigla_uf']."</td>";
                $body.= "<td>".$Rs[$i]['qualificacao']."</td>";
                $body.= "<td><a href='" . $link_ficha_receita . "'>Clique aqui</a></td>";
                $body.= "</tr>";

                $bgcolor++;

            }
        }
    }

    if ($ind_dispara_email >= 1) { //Verificação com variável auxiliar para disparar e-mail com as inconsistencias encontradas
        mail($to, $subject, $body, $headers);
    } else if (!property_exists($decoded, "error")) { //Caso nenhuma inconsistencia seja encontrada e não tenha retornado erro, envia um aviso.
        $body = 'Script executado com sucesso. Nenhuma divergencia cadastral identificada.';
        mail($to, $subject, $body, $headers);
    }
} else { //Caso não retorne nada na busca por pedidos aprovados, dispara um aviso.

    $to = $to;
    $headers = "Content-Type: text/html; charset=ISO-8859-1\r\n";
    $headers .= 'From: '.$from;
    $subject = 'UNO - Pedidos aprovados: Aviso de situacao cadastral';
    $body = 'Script executado com sucesso. Nenhum resultado na busca por pedidos aprovados em ' . date("d-m-Y", strtotime("-1 days"));

    mail($to, $subject, $body, $headers);
}
