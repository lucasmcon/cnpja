<?php
    class database{

        private function Connect(){
            $config = parse_ini_file('private/database.ini');
            $DB_Serv = $config['DB_Serv'];
            $DB_User = $config['DB_User'];
            $DB_Pass = $config['DB_Pass'];
            $DB_Name = $config['DB_Name'];

            $Conn = new mysqli($DB_Serv, $DB_User, $DB_Pass, $DB_Name);
            $Conn->set_charset("utf8");
            return $Conn;
        }

        private function CloseConn(){
            $Conn = $this->Connect();
            mysqli_close($Conn);
        }

        public function Consult($StrSQL){
			$Conn = $this->Connect();
			$Result = $Conn->query($StrSQL);
			while($Rows = $Result->fetch_assoc()){
				$ArrayReturn[] = $Rows;
			}
			$this->CloseConn();
			return @$ArrayReturn;
        }
        
        public function Execute($StrSQL){
			$Conn = $this->Connect();
			$Result = $Conn->query($StrSQL);
			$this->CloseConn();
            return $Result;
            
		}
    }
?>