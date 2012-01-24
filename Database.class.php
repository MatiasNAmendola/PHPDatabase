<?php


class Database {

    /**
     * Defines MySQL as the RDBMS vendor you want to use.
     * @access public
     */
    const MySQL = 1;

    /**
     * Defines PostgreSQL as the RDBMS vendor to use.
     * @access public
     */
    const PGSQL = 2;

    private $intVendor;

    private $strHost;
    private $strUser;
    private $strPass;
    private $strDB;

    private $objPDO;
    private $objPDOStatement;
    
    public function __construct($intVendor = self::MySQL, $objConfig = null){
        $this->intVendor = $intVendor;

        if(!is_null($objConfig) && is_object($objConfig)){
            $this->SetHost($objConfig->strHost);
            $this->SetUser($objConfig->strUser);
            $this->SetPass($objConfig->strPass);
            $this->SetDB($objConfig->strDB);
            try{
                $this->Connect();
            } catch(Exception $e){
                throw new Exception('Failed to connect to the database. See the previous exception for further details.', 1, $e);
            }
        }      
    }

    public function __destruct(){
        $this->objPDO = null;
    }

    public function SetHost($strHost){
        $this->strHost = $strHost;
    }

    public function SetUser($strUser){
        $this->strUser = $strUser;
    }

    public function SetPass($strPass){
        $this->strPass = $strPass;
    }

    public function SetDB($strDB){
        $this->strDB = $strDB;
    }

    public function Connect(){
        try{
            $this->AssertConnectParams();
            $this->objPDO = new PDO($this->CreateDSN($this->intVendor), $this->strUser, $this->strPass);
        } catch(Exception $e){
            throw new Exception('Connection params not correctly set. See the previous exception for more details', 1, $e);
        } catch(PDOException $e){
            throw new Exception('Failed to create the PDO connection object. See the previous exception for more details', 1, $e);
        }
    }

    public function Disconnect(){
        $this->objPDO = null;
        $this->objPDOStatement = null;    
    }

    public function Reconnect(){
        $this->objPDO = null;
        $this->Connect();
    }

    private function AssertConnectParams(){
        if(!isset($this->strHost) || strlen(trim($this->strHost)) == 0){
            throw new Exception('Database host has not been set.', 1);
        }

        if(!isset($this->strUser) || strlen(trim($this->strUser)) == 0){
            throw new Exception('Database user has not been set', 1);
        }

        if(!isset($this->strPass) || strlen(trim($this->strPass)) == 0){
            throw new Exception('Database password has not been set.', 1);
        }

        if(!isset($this->strDB) || strlen(trim($this->strDB)) == 0){
            throw new Exception('Database has not been set', 1);
        }
    }

    private function CreateDSN($intVendor = Database::MySQL){
        switch($intVendor){
            case self::PGSQL:
                $strVendor = 'pgsql';
                break;
            case self::MySQL:
                $strVendor = 'mysql';
                break;
            default:
                $strVendor = 'mysql';
                break;
        }

        $strDSN = $strVendor.':dbname='.$this->strDB.';host='.$this->strHost;
        return $strDSN;
    }

    public function Prepare($strQuery){
        try{
            $this->objPDOStatement = $this->objPDO->prepare($strQuery);
        } catch(PDOException $e){
            throw new Exception('Failed to prepare the query: ' . $strQuery, 1, $e);
        }
    }

    public function Bind($strKey, $strValue, $intDataType = PDO::PARAM_STR){
        if(!is_object($this->objPDOStatement)){
            throw new Exception('No statement has been prepared. Failed to bind '. $strValue . ' to ' . $strKey, 1);
        }

        try{
            $this->objPDOStatement->bindValue($strKey, $strValue, $intDataType);
        } catch(PDOException $e){
            throw new Exception('Failed to bind ' . $strValue . ' to ' . $strKey . '. See the previous exception for further details.', 1, $e);
        }
    }

    public function Query(){
        try{
            $this->objPDOStatement->execute();
        } catch(PDOException $e){
            throw new Exception('Failed to query the database. The previous exception should give you more details about the error.', 1, $e);
        }
    }

    public function GetRecord(){
        $objRecord = $this->objPDOStatement->fetch(PDO::FETCH_OBJ);
        if(!is_object($objRecord)){
            $this->objPDOStatement = null;
        }

        return $objRecord;
    }

    public function BeginTransaction(){
        if(is_object($this->objPDO)){
            return $this->objPDO->beginTransaction();
        }
    }

    public function CommitTransaction(){
        if($this->InTransaction()){
            return $this->objPDO->commit();
        }
    }

    public function RollbackTransaction(){
        if($this->InTransaction()){
            return $this->objPDO->rollBack();
        }
    }

    public function InTransaction(){
        return $this->objPDO->inTransaction();
    }
    
}
