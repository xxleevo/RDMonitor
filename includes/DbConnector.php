<?php
class DbConnector {
    private $type;
    private $host;
    private $port;
    private $user;
    private $pass;
    private $db;
    private $charset;
      
    function __construct($dbOptions) {
		$this->type = $dbOptions['type'];
        $this->host = $dbOptions['host'];
        $this->port = $dbOptions['port'];
        $this->user = $dbOptions['user'];
        $this->pass = $dbOptions['pass'];
        $this->db = $dbOptions['dbname'];
        $this->charset = $dbOptions['charset'];
    }
 
    public function getConnection() {
        // Establish connection to database
		if($this->type == 'mysql'){
			try {
				$pdo = new PDO("mysql:host=$this->host;dbname=$this->db;port=$this->port",
					$this->user,
					$this->pass,
					[PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $this->charset"]
				);
				// Set the PDO error mode to exception
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				return $pdo;
			} catch(PDOException $e) {
				die("ERROR: Could not connect. " . $e->getMessage());
			}
		} else if($this->type == 'psql'){
			$pdo = pg_connect("host=$this->host dbname=$this->db user=$this->user password=$this->pass port = $this->port")
			or die('Connection Failed: ' . pg_last_error());
			return $pdo;
		}
        die("ERROR: Could not establish connection to database.");
    }
}
?>