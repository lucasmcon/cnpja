<?php

    include('database.php');

    class vd_pedido{

        public function buscaPedidosAprovados(){

            $database = new database();


            $StrSQL = "SELECT A.cod_pedido, A.cod_cliente, C.nome_cliente, C.cnpj, C.insc_estadual, C.crt, ";
            $StrSQL.= "DATE_FORMAT(A.dt_aprovacao, '%d-%m-%Y') AS dt_aprovacao FROM vd_pedido AS A ";
            $StrSQL.= "INNER JOIN cd_nop AS B ON B.nop = A.nop ";
            $StrSQL.= "INNER JOIN cd_cliente AS C ON C.cod_cliente = A.cod_cliente ";
            $StrSQL.= "WHERE dt_aprovacao='".date("Y-m-d", strtotime("-1 days"))."' AND B.ind_estatistica_venda = 1 AND C.tp_cliente = 'J' AND C.cnpj != ''";
            
            $Rs = $database->Consult($StrSQL);

            return $Rs;

        }
        
    }


?>