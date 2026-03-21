<?php
/**
 * Team Class
 * Handles team data retrieval and management
 */

class Team {
    public $teamID = '';
    public $divisionID = 0;
    public $city = '';
    public $team = '';
    public $teamName = '';

    public function __construct($teamID) {
        $this->getTeam($teamID);
    }

    private function getTeam($teamID): bool {
        global $mysqli;
        
        $sql = "SELECT * FROM " . DB_PREFIX . "teams WHERE teamID = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $teamID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $this->teamID = $teamID;
            $this->divisionID = $row['divisionID'];
            $this->city = $row['city'];
            $this->team = $row['team'];
            $this->teamName = !empty($row['displayName']) 
                ? $row['displayName'] 
                : $row['city'] . ' ' . $row['team'];
            
            $stmt->close();
            return true;
        }
        
        $stmt->close();
        return false;
    }
}
