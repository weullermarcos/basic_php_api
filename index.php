 <?php
	
	//URL: http://localhost/igm_webservice/?codigoMunicipio=110004
	
	//parametros de conexão ao banco de dados
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "igm";
	
	$error = "";
	$codigoMunicipio;
	
	if (!empty($_GET["codigo"])){
		
		$codigoMunicipio = $_GET["codigo"];
		
		//Remove qualquer coisa que vier exceto números
		$codigoMunicipio = preg_replace("/[^0-9]/", '', $codigoMunicipio); 

		//echo "<br/>Código recebido: $codigoMunicipio";
		
		//faz a conexão
		$mysqli = new mysqli($servername, $username, $password, $dbname);
		$mysqli->set_charset("utf8");
		
		if ($mysqli->connect_error) {
			$error = "Erro de conexão com o banco de dados: " . $mysqli->mysqliect_error;
			//echo "<br/><br/>Deu ERRADO a conexão <br/><br/>";
		}
		else{
			
			//echo "<br/><br/>Deu CERTO a conexão <br/><br/>";
			
			$sql = "SELECT * FROM itens WHERE desc_codigo = '".$codigoMunicipio."'";
			//echo "Consulta: $sql";
			$sql = $mysqli->query($sql);
			$resultado = array();
			
			//verifica se encontrou resultado
			if ($sql->num_rows > 0) {
				
				//echo "<br/><br/>Achou resultado <br/><br/>";
				
				//quebrando o resultado
				$row = $sql->fetch_assoc();
				
				array_push($resultado, array("id_item" => $row["id_item"], 
											 "desc_nome" => $row["desc_nome"],
											 "desc_codigo" => $row["desc_codigo"],
											 "desc_codigo_ibge" => $row["desc_codigo_ibge"],
											 "char_estado" => $row["char_estado"],
											 "int_cluster" => $row["int_cluster"],
											 "bool_is_capital" => $row["bool_is_capital"],
											 "bool_is_estado" => $row["bool_is_capital"],
											 "bool_is_pais" => $row["bool_is_capital"],
											 "dt_created" => $row["bool_is_capital"]
											 ));
			}
			else{
				$error = "Resultado não encontrado para o código informado";
			}
			
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
		echo json_encode(array("code" => "1", "data" => $resultado));
	} 
	else {
		echo json_encode(array("code" => "0", "data" => $error));
	}
	
	//echo $error;
?> 