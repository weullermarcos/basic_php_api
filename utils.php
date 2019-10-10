<?php

	//Função para capturar IP do usuário
	function capturaIP(){
		
		if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
		{
		  $ip=$_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
		{
		  $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else
		{
		  $ip=$_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
	
	//Função para capturar data atual - TimeZone de São Paulo
	function capturaDataAtual(){
		
		$dt = new DateTime(null, new DateTimeZone('America/Sao_Paulo'));
		return $dt->format('d-m-Y H:i:s');
	}


?>