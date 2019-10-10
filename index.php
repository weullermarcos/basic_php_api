 <?php
	
	include('utils.php');
	include('settings.php');
	include('db_settings.php');
	
	//URL: http://localhost/basic_php_api/?codigo=110001
	
	$error = "";
	$cluster = "0";
	$codigoMunicipio;
	$numeroVariaveis = 29;
	$numeroValoresAtual = 3;
	$variaveis = array();
	$atual = array();
	$atualFloatIGM;
	$strLog = "";
	
	//Prepara arquivo de log
	$handle = fopen(LOG_FILE, 'a') or die('Cannot open file:  '.LOG_FILE);
	$strLog = "\n".'['. capturaDataAtual() .'] - Requisição recebida: ';
	
	if (!empty($_GET["codigo"])){
		
		$codigoMunicipio = $_GET["codigo"];
		
		//Remove qualquer coisa que vier exceto números
		$codigoMunicipio = preg_replace("/[^0-9]/", '', $codigoMunicipio); 

		//echo "<br/>Código recebido: $codigoMunicipio";
		
		//faz a conexão
		$mysqli = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
		$mysqli->set_charset("utf8");
		
		if ($mysqli->connect_error) {
			$error = "Erro de conexão com o banco de dados: " . $mysqli->mysqliect_error;
			//echo "<br/><br/>Deu ERRADO a conexão <br/><br/>";
		}
		else{
			
/***************************** CONSULTAR CLUSTER PARA MUNICIPIO *****************************************/
			
			$sql = "SELECT int_cluster FROM itens WHERE desc_codigo = '".$codigoMunicipio."'";
			$sql = $mysqli->query($sql);
			
			//verifica se encontrou cluster
			if ($sql->num_rows > 0) {
				
				$row = $sql->fetch_assoc();	
				$cluster = $row["int_cluster"];
				
				//echo "<br/><br/>CLUSTER: $cluster<br/><br/>";
			}
			else{
				$error = "Resultado não encontrado para o código informado";
			}
/*******************************************************************************************************/		
/***************************** CONSULTAR POSIÇÃO DO MUNICIPIO NO CLUSTER *******************************/

			//SQl retorna uma lista ordenada dos municipios de acordo com o cluster do municipio 
			//e para o ano de 2018 
			$sql = "SELECT itens.id_item, itens.desc_codigo, itens.desc_nome, itens.char_estado, 
						   itens.int_cluster, itens_ano.float_igm, itens_ano.id_ano 
					FROM itens
					INNER JOIN itens_ano ON itens.id_item = itens_ano.id_item				
					WHERE itens.int_cluster = '".$cluster."' AND itens_ano.id_ano = '3'
					ORDER BY itens_ano.float_igm DESC";
							
			
			$sql = $mysqli->query($sql);

			//Percorre resultado da consulta para achar posição do municipio
			if ($sql->num_rows > 0) {
				
				$posicao = 0;
				while($row = $sql->fetch_assoc()) {
					
					$posicao++;
					
					if($row["desc_codigo"] == $codigoMunicipio){
						
						$atualFloatIGM = $row["float_igm"];
						break;
					}
				}
			}
			else{
				$error = "Resultado não encontrado para o código informado";
			}			

/*******************************************************************************************************/		
/**************************** CONSULTAR DADOS DO MUNICIPIO PARA CABEÇALHO ******************************/
			
			$sql = "SELECT itens.id_item, itens.desc_codigo, itens.desc_nome, itens.char_estado, 
						   itens.int_cluster, itens_ano.int_populacao 
					FROM itens
					INNER JOIN itens_ano ON itens.id_item = itens_ano.id_item
					WHERE itens.desc_codigo = '".$codigoMunicipio."' 
					AND itens_ano.id_ano = '3'";
			
			//echo "Consulta: $sql";
			$sql = $mysqli->query($sql);
			
			//verifica se encontrou resultado
			if ($sql->num_rows > 0) {
				
				//quebrando o resultado
				$row = $sql->fetch_assoc();
			}
			else{
				$error = "Resultado não encontrado para o código informado";
			}

/*******************************************************************************************************/		
/************************************ CONSULTAR VARIÁVEIS **********************************************/

			$sql = "SELECT itens.id_item, itens.desc_codigo, itens.desc_nome, variaveis_ano.id_variavel,
						   variaveis_ano.desc_valor, variaveis_ano.desc_meta
					FROM itens
					INNER JOIN variaveis_ano ON itens.id_item = variaveis_ano.id_item
					WHERE itens.desc_codigo = '".$codigoMunicipio."' 
					AND variaveis_ano.id_ano = '3'";
			
			//echo "Consulta: $sql";
			
			$sql = $mysqli->query($sql);
			
			//verifica se encontrou 29 variáveis
			if ($sql->num_rows == $numeroVariaveis) {
				
				while($rowVariables = $sql->fetch_assoc()) {
					
					array_push($variaveis, array("id_variavel" => $rowVariables["id_variavel"], 
												 "desc_valor" => $rowVariables["desc_valor"],
												 "desc_meta" => $rowVariables["desc_meta"]
												 ));	
				}	
			}
			else{
				$error = "Resultado não encontrado para o código informado";
			}
						
/*******************************************************************************************************/		
/********************************* CONSULTAR DADOS "ATUAL" - DADOS OCULTOS *****************************/	
		
			$sql = "SELECT itens.id_item, itens.desc_codigo, itens.desc_nome, dimensoes_ano.id_ano,
						   dimensoes_ano.id_dimensao, dimensoes_ano.float_valor
					FROM itens
					INNER JOIN dimensoes_ano ON itens.id_item = dimensoes_ano.id_item
					WHERE itens.desc_codigo = '".$codigoMunicipio."' 
					AND dimensoes_ano.id_ano = '3'";
			
			//echo "Consulta: $sql";
			$sql = $mysqli->query($sql);
			
			//verifica se encontrou 3 valores para a área da planilha "Atual"
			if ($sql->num_rows == $numeroValoresAtual) {
				
				while($rowAtual = $sql->fetch_assoc()) {
					
					array_push($atual, array("id_dimensao" => $rowAtual["id_dimensao"], 
											 "float_valor" => $rowAtual["float_valor"]
												 ));	
				}	
			}
			else{
				$error = "Resultado não encontrado para o código informado";
			}
			
/*******************************************************************************************************/		
		
			//Fechando conexão com banco de dados
			$mysqli->close();
		}
	}
	else{
		$error = "Código vazio";
	}

	//Prepara JSON
	header('Content-Type: application/json');
	
	if (empty($error)){
		
		$strLog .= "STATUS: SUCESSO - IP: [" .capturaIP()."] - CÓDIGO RECEBIDO: ".$codigoMunicipio;
		
		echo json_encode(array("status" => "SUCESSO", 
							   "id_item" => $row["id_item"],
								"desc_codigo" => $row["desc_codigo"],
								"desc_nome" => $row["desc_nome"],
								"int_populacao" => $row["int_populacao"],
								"char_estado" => $row["char_estado"],
								"int_cluster" => $row["int_cluster"],
								"ranking_atual" => "$posicao",
								"atual_float_valor_1" => $atual[0]["float_valor"],
								"atual_float_valor_2" => $atual[1]["float_valor"],
								"atual_float_valor_3" => $atual[2]["float_valor"],
								"atual_float_igm" => $atualFloatIGM,
								"financas_desc_valor_1" => $variaveis[0]["desc_valor"],
								"financas_desc_valor_2" => $variaveis[1]["desc_valor"],
								"financas_desc_valor_3" => $variaveis[2]["desc_valor"],
								"financas_desc_valor_4" => $variaveis[3]["desc_valor"],
								"financas_desc_valor_5" => $variaveis[4]["desc_valor"],
								"financas_desc_valor_6" => $variaveis[5]["desc_valor"],
								"financas_desc_valor_7" => $variaveis[6]["desc_valor"],
								"financas_desc_valor_8" => $variaveis[7]["desc_valor"],
								"financas_desc_valor_9" => $variaveis[8]["desc_valor"],
								"gestao_desc_valor_1" => $variaveis[9]["desc_valor"],
								"gestao_desc_valor_2" => $variaveis[10]["desc_valor"],
								"gestao_desc_valor_3" => $variaveis[11]["desc_valor"],
								"gestao_desc_valor_4" => $variaveis[12]["desc_valor"],
								"gestao_desc_valor_5" => $variaveis[13]["desc_valor"],
								"gestao_desc_valor_6" => $variaveis[14]["desc_valor"],
								"gestao_desc_valor_7" => $variaveis[15]["desc_valor"],
								"gestao_desc_valor_8" => $variaveis[16]["desc_valor"],
								"gestao_desc_valor_9" => $variaveis[17]["desc_valor"],
								"gestao_desc_valor_10" => $variaveis[18]["desc_valor"],
								"desempenho_desc_valor_1" => $variaveis[19]["desc_valor"],
								"desempenho_desc_valor_2" => $variaveis[20]["desc_valor"],
								"desempenho_desc_valor_3" => $variaveis[21]["desc_valor"],
								"desempenho_desc_valor_4" => $variaveis[22]["desc_valor"],
								"desempenho_desc_valor_5" => $variaveis[23]["desc_valor"],
								"desempenho_desc_valor_6" => $variaveis[24]["desc_valor"],
								"desempenho_desc_valor_7" => $variaveis[25]["desc_valor"],
								"desempenho_desc_valor_8" => $variaveis[26]["desc_valor"],
								"desempenho_desc_valor_9" => $variaveis[27]["desc_valor"],
								"desempenho_desc_valor_10" => $variaveis[28]["desc_valor"],
								"financas_desc_meta_1" => $variaveis[0]["desc_meta"],
								"financas_desc_meta_2" => $variaveis[1]["desc_meta"],
								"financas_desc_meta_3" => $variaveis[2]["desc_meta"],
								"financas_desc_meta_4" => $variaveis[3]["desc_meta"],
								"financas_desc_meta_5" => $variaveis[4]["desc_meta"],
								"financas_desc_meta_6" => $variaveis[5]["desc_meta"],
								"financas_desc_meta_7" => $variaveis[6]["desc_meta"],
								"financas_desc_meta_8" => $variaveis[7]["desc_meta"],
								"financas_desc_meta_9" => $variaveis[8]["desc_meta"],
								"gestao_desc_meta_1" => $variaveis[9]["desc_meta"],
								"gestao_desc_meta_2" => $variaveis[10]["desc_meta"],
								"gestao_desc_meta_3" => $variaveis[11]["desc_meta"],
								"gestao_desc_meta_4" => $variaveis[12]["desc_meta"],
								"gestao_desc_meta_5" => $variaveis[13]["desc_meta"],
								"gestao_desc_meta_6" => $variaveis[14]["desc_meta"],
								"gestao_desc_meta_7" => $variaveis[15]["desc_meta"],
								"gestao_desc_meta_8" => $variaveis[16]["desc_meta"],
								"gestao_desc_meta_9" => $variaveis[17]["desc_meta"],
								"gestao_desc_meta_10" => $variaveis[18]["desc_meta"],
								"desempenho_desc_meta_1" => $variaveis[19]["desc_meta"],
								"desempenho_desc_meta_2" => $variaveis[20]["desc_meta"],
								"desempenho_desc_meta_3" => $variaveis[21]["desc_meta"],
								"desempenho_desc_meta_4" => $variaveis[22]["desc_meta"],
								"desempenho_desc_meta_5" => $variaveis[23]["desc_meta"],
								"desempenho_desc_meta_6" => $variaveis[24]["desc_meta"],
								"desempenho_desc_meta_7" => $variaveis[25]["desc_meta"],
								"desempenho_desc_meta_8" => $variaveis[26]["desc_meta"],
								"desempenho_desc_meta_9" => $variaveis[27]["desc_meta"],
								"desempenho_desc_meta_10" => $variaveis[28]["desc_meta"]
								));
	} 
	else {
		
		$strLog .= "STATUS: ERRO - IP: [". capturaIP() ."] - ".$error;
		
		if (!empty($codigoMunicipio)){
			
			$strLog .= " CÓDIGO: ".$codigoMunicipio;
		}
			
		echo json_encode(array("status" => "ERRO", "data" => $error));
	}
	
	//escreve no arquivo
	fwrite($handle, $strLog);
	fclose($handle); 
	
	//echo $error;
?> 