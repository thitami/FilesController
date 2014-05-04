<?php

/*
 * DBController class handles all the DB related tasks.
 * Initially, it gets the environment variables from an external file (config.php),
 * creates a new PDO instance with these details, and finally builds the Database, using the
 * following steps:
 * 
 * 1) Drops an existing DB, if exists
 * 2) Creates a new DB, which will then be used for data storage
 * 3) Creates the Teams, Players and TeamsPlayers tables
 * 4) Handles the data and subsequently stores them to the proper tables
 * 
 * author: Theodoros Moschos
 */

class DBController
{
    
    // the PDO connection instance
    private $connection; 
    
    // the username for the PDO DB connection
    public $username;
    
    // the password for the PDO DB connection
    public $password;
    
    // the hostname for the PDO DB connection
    public $hostname;
    
    // the name of the DB that will be used
    public $dbName;
    
    // the username of the new DB user
    public $dbUsername;
    
    // the password of the new DB user
    public $dbPassword;
  
    
    // DBController class constructor
    public function __construct()
    {
       // get the PDO DB details
       $this->getEnvVariables();
       
       //create a new PDO connection instance
       $this->connection = new PDO('mysql:host='. $this->hostname, $this->username, $this->password);
       $this->connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
       
       // starts building the DB
       $this->buildDatabase();
    
    }
   
    /*
     *  Reads the configuration file and assigns the variables to the corresponding
     *  class properties
     */
    private function getEnvVariables()
    {
        if ( ! file_exists('config.php'))
           throw new Exception ("Config file does not exist", 1);
       
       include 'config.php'; 
       $this->hostname   = $hostname;
       $this->username   = $username;
       $this->password   = $password;
       
       $dbName           = "`".str_replace("`","``",$dbName)."`";
       $this->dbName     = $dbName;
       $this->dbUsername = $dbUsername;
       $this->dbPassword = $dbPassword;
       
    }
    
    function dd($param)
    {
        echo "\n DD Results: \n";
        echo "<pre>";
        var_dump($param);
        echo "</pre>";
        die;
    }


    /*
     *  Builds the database and grant the permissions
     *  and calls createTables() to create the table for the provided DB
     */
    private function buildDatabase()
    { 
        try {
            
            $this->connection->exec("DROP DATABASE IF EXISTS". $this->dbName);
            
            $createQuery = " 
                             CREATE DATABASE $this->dbName;
                             CREATE USER '$this->dbUsername'@'$this->hostname' IDENTIFIED BY '$this->dbPassword';
                             GRANT ALL ON `$this->dbName`.* TO '$this->dbUsername'@'$this->hostname';
                             FLUSH PRIVILEGES;    
                           ";       
            
                              
                              
            //echo $createQuery; die;
            $this->connection->exec($createQuery)
            or die(print_r($this->connection->errorInfo(), true));

  
            $this->connection->query("use ". $this->dbName);
            
            $this->createTables();
        }
        catch (PDOException $e)
        {
            die("DB ERROR: Exception while trying to build the DB: ". $e->getMessage());
        }
        
    } // end of buildDatabase()
    
    /*
     * Creates all the required tables: 
     * 1) Players
     * 2) Teams
     * 3) TeamPlayers
     */
    public function createTables()
    {
        try{
                // create the Teams table
                $teamsCreationQuery = " 
                                         CREATE TABLE IF NOT EXISTS `teams`(
                                             teamId INT(11) AUTO_INCREMENT KEY,
                                             name VARCHAR( 50 ) NOT NULL                                         
                                         );";
                $this->connection->exec($teamsCreationQuery) ;
                
                echo "\n<br/>Teams table created"; 
                
                // Create the Players table
                $playersCreationQuery = " 
                                         CREATE TABLE IF NOT EXISTS `players`(
                                         playerId INT( 11 ) AUTO_INCREMENT PRIMARY KEY,
                                         firstname VARCHAR( 50 ) NOT NULL,
                                         lastname VARCHAR( 50 ) NOT NULL,
                                         number INT (11) NOT NULL
                                        );";
 
                
                $this->connection->exec($playersCreationQuery);
                
                echo "\n<br/> Players table created"; 
                
                // Create the TeamPlayers table
                $teamPlayersCreationQuery = " 
                                         CREATE TABLE IF NOT EXISTS `teamPlayers`(
                                         teamPlayerId INT(11) AUTO_INCREMENT PRIMARY KEY ,
                                         teamId INT(11),
                                         name VARCHAR( 50 ) NOT NULL,
                                         playerId INT(11) NOT NULL
                                      );";
                
                $this->connection->exec($teamPlayersCreationQuery) ;
                //  or die(print_r($this->connection->errorInfo(), true));                
                                //        or die(print_r($this->connection->errorInfo(), true));
                echo "\n<br/> TeamPlayers table created"; 
        }
         catch (PDOException $e)
         {
            die("DB ERROR: Exception while trying to create the tables: ". $e->getMessage()); 
         }
         
    } // end of createTables()

    /*
     * Takes a FileController instance as parameter and inserts the data 
     * to the related table
     */
    public function storeData(FileController $parserInstance)
    {
      try
      {
            foreach ($parserInstance->mergedAllPlayers as $player)
            {

                  $playersQuery = "INSERT IGNORE INTO `players` ( playerId, firstname, lastname, number)"
                          . "       VALUES (:playerId, :firstname, :lastname, :number)";

                  //prepare the statement...
                  $query = $this->connection->prepare($playersQuery);

                  // ... and then execute it
                  $query->execute(
                                  array(
                                        ':playerId' => $player->id,
                                        ':firstname'=> $player->firstName,
                                        ':lastname' => $player->lastName,
                                        ':number'   => $player->number
                                       )
                                 );
            }
            echo "\n <br/> Players data stored!" ;

            if (! is_array($parserInstance->allTeams))
                throw new Exception ('Invalid parameter format of AllPlayers', 1);

            /* Read the allTeams property of the FileController instance
             * and iterate through it.
             * For each team, insert a new record into the DB with its teamId and name.
             */
            foreach ($parserInstance->allTeams as $team )
            {

                  $teamPlayersQuery = "INSERT IGNORE  INTO `teams` ( teamId, name)"
                          . "       VALUES (:teamId, :name)";

                  //prepare the statement...
                  $query = $this->connection->prepare($teamPlayersQuery);

                  // ...and execute it...
                  $query->execute(
                                  array(
                                        ':teamId'   => $team->meta->teamId,
                                        ':name'     => $team->meta->teamName
                                       )
                                 );

                /*
                 * Iterate through each team's player and insert into the DB
                 *  their teamId, teamName and playerId
                 */
                foreach ($team->players as $player)
                {

                    $teamPlayersQuery = "INSERT IGNORE  INTO `teamPlayers` ( teamId, name, playerId)"
                                      . " VALUES (:teamId, :name, :playerId)";

                    // prepare the statement...
                    $query = $this->connection->prepare($teamPlayersQuery);

                    // ... and execute it 
                    $query->execute(
                                  array(
                                        ':teamId'   => $team->meta->teamId,
                                        ':name'     => $team->meta->teamName,
                                        ':playerId' => $player->id
                                       )
                                 );

                } // end foreach player

            } // end foreach team
      }
      catch(PDOException $e)
      {
          die("DB ERROR: Exception thrown while trying to store the data" .  $e->getMessage());
      }
      
     } // end of storeData()
    
} // end of DBController class
