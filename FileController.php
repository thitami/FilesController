<?php

/*
 * FilesController is the class that primarily parses the JSON files to
 * a set of Objects/Arrays, and prepares the data before sending them
 * to DBController with the scope to be stored.
 * 
 * author: Theodoros Moschos
 */

class FilesController 
{
  
  // contains the names of the files that contain the JSON data  
  protected $filenames;  
  
  // array to store the Teams data
  protected $teams=[];
  
  // the file that contains the Configuration variables
  protected $configFile = 'config.php';
  
  public    $allTeams=[];
  public    $allPlayers=[];
  
  // Array which facilitates all the players of all teams combined
  public    $mergedAllPlayers=[];
  
  
  //FileController constructor
  public function __construct()
  {
      //assign the files that contain the data to an Array
      $this->filenames = array("BayernMunich.json","Germany.json");
      
      //
      $this->fetchFiles();
      
      $allTeams = $this->getTeamsData();
      $this->allUniquePlayersIds = $this->getUniquePlayersIds($this->allPlayers);
      
      $this->loadDataBase();
          
  }
  
  /*
   * Helper function for debugging purposes
   */
  function dd($param)
  {
      echo "\n DD Results: \n";
      echo "<pre>";
      var_dump($param);
      echo "</pre>";
      die;
  }
  
  /*
   *  Assigns the files data to a class property,
   *  in order to be available within the class 
   */
  function fetchFiles()
  {
    
     if ( ! is_array($this->filenames))
      throw new Exception("Error while processing", 1);
           
      foreach ($this->filenames as $filename)
      {
          if ( ! file_exists($filename))
              throw new Exception ("File: " , $filename. " not found", 1);

            $this->teams[] = file_get_contents($filename);
       }
  
  } // end of fetchFiles

  //checks if the provided is string is valid JSON format
  private function is_json($string) 
  {
      json_decode($string);
      
      return (json_last_error() == JSON_ERROR_NONE);
      
  } // end of is_json


  
  private function getTeamsData()
  {
    // Temp array to store  
    $teams =[];
    
   /* Loop through the teams variable and create an array of objects,
    * for the teams
    */
    foreach ($this->teams as $team_string)
    {
        
         if ( is_object(json_decode($team_string)))
         {        
             $this->allTeams[] = json_decode($team_string);
         }
    } // end foreach teams
    
   
    if ( is_array($this->allTeams))
    {
        foreach ( $this->allTeams as $team)
        {
            $teams[$team->meta->teamId]["teamId"][]   = $team->meta->teamId;
            $teams[$team->meta->teamId]["teamName"][] = $team->meta->teamName;
            $teams[$team->meta->teamId]["players"][]  = $team->players;
            $this->allPlayers[] = $team->players; 
        }
    }   
    
    foreach ($this->allPlayers as $teamPlayers)
    {
       foreach ($teamPlayers as $teamPlayer)
          array_push($this->mergedAllPlayers, $teamPlayer);
    }
    
    return $teams;    
    
  } //end of getPlayers
   
   // Not used on current version  
   function getUniquePlayersIds($players)
   {
        $allPlayersIds =[];
         foreach ($players as $player => $player_details)
         {
             foreach ($player_details as $player)
             {
                 $allPlayersIds[]= $player->id;       
             }
         }

         $uniquePlayersIds = array_unique($allPlayersIds);
        
     return $uniquePlayersIds; 
          
    } // end of getUniquePlayerIds
    
    /* load the DBController class and stores the suitable data,
     * by passing an instance of the current class
     */
    function loadDatabase()
    {
        //firstly, check whether the provided file exists 
        if ( ! file_exists($this->configFile) )
          throw new Exception ('Exception thrown: DB class not found', 1);
      
        include 'DBController.php';
        
        //check if the DBController class has been loaded
        if ( ! class_exists('DBController'))
            throw new Exception ('Exception thrown: DBController class not found', 1);
        
        //call the DB Controller and store the data
        $dbController = new DBController();
        $dbController->storeData($this);
        
    } // end of loadDatabase
              
  } // end of FileController

  //create and instance and ...ready to go!
 new FilesController();
 
 