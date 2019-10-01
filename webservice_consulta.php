 <?php
	$error = "";
	if (isset($_GET["cpf"]) && isset($_GET["cod"])){
		$cpfs = htmlentities($_GET["cpf"], ENT_QUOTES, 'UTF-8');
		$codigo = htmlentities($_GET["cod"], ENT_QUOTES, 'UTF-8'); //TODO: Registrar acesso
		
		//Trata o(s) cpf(s)
		$cpfs = preg_replace("/[^0-9,]/", '', $cpfs); //Remove qualquer coisa que vier exceto números e vírgulas
		$cpfs = explode(',', $cpfs); //Quebra em um array
		for($i = 0; $i < sizeof($cpfs); $i++){ //itera o array formatando corretamente todos os cpfs
			$cpfs[$i] = preg_replace("/\D/", '', $cpfs[$i]);
			$cpfs[$i] = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cpfs[$i]);
		}
		$cpfs_consulta = implode(",", $cpfs);
		$cpfs_where = "'" . implode("','", $cpfs) . "'";//Refaz a string
		$servername = "asdf";
		$username = "asdf";
		$password = "asdasdf";
		$dbname = "asdfasdf";

		$mysqli = new mysqli($servername, $username, $password, $dbname);
		$mysqli->set_charset("utf8");
		if ($mysqli->connect_error) {
			$error = "Erro de conexão com o banco de dados: " . $mysqli->mysqliect_error;
		}
		$sql = "SELECT id FROM convenios WHERE convenio_id = '".$codigo."'";
		$sql = $mysqli->query($sql);
		$resultado = array();
		if ($sql->num_rows > 0) {
			while($row = $sql->fetch_assoc()) {
				$codigo = $row['id'];
			}
			//Convênio encontrado
			$remote_address = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '';
			$forwarded_address = isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : '';
			//Insere registro da consulta
			$sql = "INSERT INTO consultas (cpf, convenio_id, remote_address, forwarded_address, datetime) values ('".$cpfs_consulta."', '".$codigo."','".$remote_address."','".$forwarded_address."',NOW())";
			$mysqli->query($sql);
				//Faz a consulta
			$sql = "SELECT cpf FROM tblcpf WHERE cpf IN (" . $cpfs_where . ")";
			$sql = $mysqli->query($sql);
			$resultado = array();
			$cpfs_encontrados = array();
			if ($sql->num_rows > 0) {
				$quantidade_encontrados = 0;
				$quantidade_nao_encontrados = 0;
				while($row = $sql->fetch_assoc()) {
					$quantidade_encontrados++;
					array_push($resultado, array("cpf" => $row["cpf"], "status" => "ENCONTRADO"));
					array_push($cpfs_encontrados, $row['cpf']);
					unset($cpfs[array_search($row["cpf"], $cpfs)]);
				}
				foreach($cpfs as $cpf_nao_encontrado){
					array_push($resultado, array("cpf" => $cpf_nao_encontrado, "status" => "NÃO ENCONTRADO"));
					$quantidade_nao_encontrados++;
				}
			} else {
				$error = "Nenhum resultado encontrado.";
			}
		} else {
			$error = "Convênio não encontrado.";
		}
		$mysqli->close();
	} else {
		$error = "CPF ou Código não fornecido.";
	}

	header('Content-Type: application/json');
	if (empty($error)){
		$cpfs_encontrados = implode("','", $cpfs_encontrados);
		echo json_encode(array("code" => "1", "data" => $resultado, "quantidade_encontrados" => $quantidade_encontrados, "quantidade_nao_encontrados" => $quantidade_nao_encontrados, "cpfs_encontrados" => $cpfs_encontrados));
	} else {
		echo json_encode(array("code" => "0", "data" => $error));
	}
?> 