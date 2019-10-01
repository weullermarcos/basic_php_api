 <?php
	$error = "";
	if (isset($_GET["cpf"]) && isset($_GET["cod"]) && isset($_GET["desconto"])){
		$cpfs = htmlentities($_GET["cpf"], ENT_QUOTES, 'UTF-8');
		$codigo = htmlentities($_GET["cod"], ENT_QUOTES, 'UTF-8');
		$desconto = htmlentities($_GET["desconto"], ENT_QUOTES, 'UTF-8');
		
		//Trata o(s) cpf(s)
		$cpfs = preg_replace("/[^0-9,]/", '', $cpfs); //Remove qualquer coisa que vier exceto números e vírgulas
		$cpfs = explode(',', $cpfs); //Quebra em um array
		for($i = 0; $i < sizeof($cpfs); $i++){ //itera o array formatando corretamente todos os cpfs
			$cpfs[$i] = preg_replace("/\D/", '', $cpfs[$i]);
			$cpfs[$i] = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cpfs[$i]);
		}

		$servername = "odin";
		$username = "mysql";
		$password = "m15sql";
		$dbname = "cadastronacional2";

		$mysqli = new mysqli($servername, $username, $password, $dbname);
		$mysqli->set_charset("utf8");
		if ($mysqli->connect_error) {
			$error = "Erro de conexão com o banco de dados: " . $mysqli->mysqliect_error;
		} else {
			$sql = "SELECT id FROM convenios WHERE convenio_id = '".$codigo."'";
			$sql = $mysqli->query($sql);
			$resultado = array();
			if ($sql->num_rows > 0) {
				while($row = $sql->fetch_assoc()) {
					$codigo = $row['id'];
				}
				foreach($cpfs as $cpf){
					$sql = "INSERT INTO descontos (cpf, convenio_id, datetime, desconto) values ('".$cpf."', '".$codigo."',NOW(),'".$desconto."')";
					$mysqli->query($sql);
				}
			} else {
				$error = "Convênio não encontrado.";
			}
		}
		$mysqli->close();
	} else {
		$error = "CPF, Código do Convênio ou Desconto não fornecido.";
	}

	header('Content-Type: application/json');
	if (empty($error)){
		echo json_encode(array("code" => "1", "data" => "Desconto salvo com sucesso!"));
	} else {
		echo json_encode(array("code" => "0", "data" => $error));
	}
?> 