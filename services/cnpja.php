<?php

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

?>