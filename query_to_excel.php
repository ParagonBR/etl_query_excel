<?php
require 'PHP_XLSX/xlsxwriter.class.php';

set_time_limit(300);
ini_set('memory_limit', '-1');
/**
 * Inicio da classe
* @param string $host ip ou hostnamo do banco de dados
* @param string $db nome do database 
* @param string $usuario nome do usuario 
* @param string $senha senha para acesso ao banco de dados
**/

class sqlToExcel
{   
    private $servidor;
    private $banco;
    private $usuario;
    private $senha;
    public $conexao;



    public function __construct($host,$db,$usuario,$senha) {
        $this->servidor = $host;
        $this->banco = $db;
        $this->usuario = $usuario;
        $this->senha = $senha;
        $dsn = "pgsql:host=$this->servidor;port=5432;dbname=$this->banco;user=$this->usuario;password=$this->senha";
        try{
	        $this->conexao = new PDO($dsn);
        }
        catch(PDOException $e){
            echo $e->getMessage();
        }
    }

/**
 * Converte o resultado da consulta para xlsx, retorna o caminho absoluto do arquivo, salvo na pasta tmp
* @param string $nome_arquivo localização concatenada com nome, ex arq, sem extensão
* @param string $consulta query que sera executada no banco Replica_Enuan
**/

    public function queryToExcel($consulta,$nome_arquivo){
        $conn = $this->conexao;
        $stmt = $conn->prepare($consulta);
        if($stmt->execute()){
            $response = [];
            // Pega retorno da consulta e monta array
            while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
                $response[] = $result;
            } 
            // Monta array com header
            $header = array_keys($response[0]);
            $writer = new XLSXWriter();
            $arquivo = '/tmp/'.$nome_arquivo.".xlsx";
            array_unshift($response,$header);
            $writer->writeSheet($response);
            $writer->writeToFile($arquivo);
            return $arquivo;
        }
        else{
            echo "<br>Erro em consulta:";
            $arr = $stmt->errorInfo();
            echo json_encode($arr);
        }

    }
}

/**
* 
* @param string $servidor Ip do servidor FTP, sem ftp:// , apenas o host ou ip
* @param string $usuario_ftp Usuario 
* @param string $senha_ftp
**/

class FTP{
    private $servidor;
    private $usuario_ftp;
    private $senha_ftp;
    private $conn_id;
    public function __construct($servidor,$usuario_ftp,$senha_ftp) {
            $this->servidor = $servidor;
            $this->usuario_ftp = $usuario_ftp;
            $this->senha_ftp = $senha_ftp;
    
            // login
            $this->conn_id = ftp_connect($this->servidor);
            ftp_login($this->conn_id, $this->usuario_ftp, $this->senha_ftp);
    }
    
    /**
    * @param string $nome_arquivo localização concatenada com nome, ex /tmp/arq.xlsx
    * @param string $destino Pasta destino concatenada com nome e extensão do arquivo no qual será salvo ex: /pasta/arq.xlsx
    **/
    
    public function enviaFtp($nome_arquivo,$destino)
    {   $info_arquivo = pathinfo($nome_arquivo);

        $fp = fopen($nome_arquivo, 'r');
        $destino_final = $destino.$info_arquivo['basename'];
        // upload 
        if (ftp_fput($this->conn_id, $destino_final, $fp , FTP_ASCII)) {
            echo "Sucesso no upload do arquivo $destino_final \n<br>";
            fclose($fp);
            unlink($nome_arquivo);
            return true;
        } else {
            echo "Ocorreu um problema ao fazer o upload do arquivo  $destino_final \n<br>";
            fclose($fp);
            unlink($nome_arquivo);
            return false;
        }
        }

    public function fechaFTP(){
        ftp_close($this->conn_id);
    }
    }


