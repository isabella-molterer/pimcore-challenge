<?php

namespace App\Command;

use DateTime;
use Carbon\Carbon;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TeamImportCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('teamimport:command')
            ->setDescription('Team import command');
    }

protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeInfo('Initializing...');

        $fileOverview = PIMCORE_PROJECT_ROOT . '\public\var\assets\Data\teams_overview.xlsx';
        $fileTeams = PIMCORE_PROJECT_ROOT . '\public\var\assets\Data\teams.xlsx';
        $fileMembers = [
            '',
            PIMCORE_PROJECT_ROOT . '\public\var\assets\Data\team_gryffindor.xlsx',
            PIMCORE_PROJECT_ROOT . '\public\var\assets\Data\team_hufflepuff.xlsx',
            PIMCORE_PROJECT_ROOT . '\public\var\assets\Data\team_ravenclaw.xlsx',
            PIMCORE_PROJECT_ROOT . '\public\var\assets\Data\team_slytherin.xlsx',
            PIMCORE_PROJECT_ROOT . '\public\var\assets\Data\team_bulgaria.xlsx'
        ];

        $this->importTeamOverviews($fileOverview, $fileTeams, 2, $fileMembers, 10);

        $this->writeInfo('Import completed!');

        return 1;
    }

    function importTeamOverviews($fileNameOverview, $fileNameTeams, $folderIdTeam, $fileNameMembers, $folderIdMembers) {
        // read in data from Excel file
        $worksheet = $this->readInExcel($fileNameOverview);

        // create teams with team members
        $teams = $this->importTeam($fileNameTeams, $folderIdTeam, $fileNameMembers, $folderIdMembers);

        foreach ($worksheet->getRowIterator() as $idx => $row) {
            if ($idx !== 1) { // skip first row = headers
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(FALSE);

                // Retrieve cell values
                $overviewName = $worksheet->getCell('A' . $idx)->getValue();
                $overviewDescription = $worksheet->getCell('B' . $idx)->getValue();
                $source = $worksheet->getCell('C' . $idx)->getValue();

                // Create a new object
                $dataObject = new DataObject\TeamOverview();
                $dataObject->setClassName('TeamOverview');
                $dataObject->setKey(\Pimcore\Model\Element\Service::getValidKey($overviewName, 'object'));
                $dataObject->setParentId(1); // Home folder

                // Set values based on Excel data
                $dataObject->setName($overviewName);
                $dataObject->setDescription($overviewDescription);

                $link = new DataObject\Data\Link();
                $link->setPath($source);
                $link->setText($overviewName);
                $link->setTitle($overviewName);
                $dataObject->setSource($link);

                // Set relations
                $dataObject->setTeams($teams[($idx - 1)]);

                // Save and publish object
                $dataObject->setPublished(true);
                $dataObject->save();
            }
        };
    }


    function importTeam($fileNameTeams, $folderIdTeam, $fileNameMembers, $folderIdMembers) {
        // read in teams data from Excel file
        $worksheet = $this->readInExcel($fileNameTeams);

        $internationalTeams = [];
        $hogwartsTeams = [];

        foreach ($worksheet->getRowIterator() as $idx => $row) {
            if ($idx !== 1) { // skip first row = headers
                // create team members
                $members = $this->importMembers($fileNameMembers[$idx-1], $folderIdMembers);

                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(FALSE);

                // data values
                $teamName = $worksheet->getCell('A' . $idx)->getValue();
                $teamDescription = $worksheet->getCell('B' . $idx)->getValue();
                $teamTrainer = $worksheet->getCell('C' . $idx)->getValue();
                $latitude = $worksheet->getCell('E' . $idx)->getValue();
                $longitude = $worksheet->getCell('F' . $idx)->getValue();
                $source = $worksheet->getCell('G' . $idx)->getValue();
                $isInternational = $worksheet->getCell('H' . $idx)->getValue();

                // Create a new object
                $dataObject = new DataObject\Team();
                $dataObject->setClassName('Team');
                $dataObject->setKey(\Pimcore\Model\Element\Service::getValidKey($teamName, 'object'));
                $dataObject->setParentId($folderIdTeam);

                // Set values based on Excel data
                $dataObject->setName($teamName);
                $dataObject->setDescription($teamDescription);
                $dataObject->setTrainer($teamTrainer);
                $dataObject->setIsInternational($isInternational);

                $foundedIn = $worksheet->getCell('D' . $idx)->getValue();
                $foundedInDate = new DateTime($foundedIn);
                // $foundedInDate = DateTime::createFromFormat('Y-m-d', $foundedIn);
                $dataObject->setFoundedIn(Carbon::instance($foundedInDate));

                // Set relations
                $dataObject->setCaptain($members[0]);
                $dataObject->setMembers($members);

                $link = new DataObject\Data\Link();
                $link->setPath($source);
                $link->setText($teamName);
                $link->setTitle($teamName);
                $dataObject->setSource($link);

                $location = new DataObject\Data\GeoCoordinates($latitude, $longitude);
                $dataObject->setLocation($location);

                // Save and publish object
                $dataObject->setPublished(true);
                $dataObject->save();

                if ($isInternational == true) {
                    array_push($internationalTeams, $dataObject);
                } else {
                    array_push($hogwartsTeams, $dataObject);
                }
            }
        };

        return ['', $hogwartsTeams, $internationalTeams];
    }

    function importMembers($filename, $folderId) {
        $this->writeInfo('Importing members...', $folderId);

        // read in data from Excel file
        $worksheet = $this->readInExcel($filename);
        $members = [];

        foreach ($worksheet->getRowIterator() as $idx => $row) {
            if ($idx !== 1) { // skip first row = headers
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(FALSE);

                // Retrieve cell values
                $memberName = $worksheet->getCell('A' . $idx)->getValue();
                $memberNumber = $worksheet->getCell('B' . $idx)->getValue();
                $memberDuty = $worksheet->getCell('C' . $idx)->getValue();
                $memberIsSubstitute = $worksheet->getCell('D' . $idx)->getValue();
                $memberAge = $worksheet->getCell('E' . $idx)->getValue();

                // Create a new object
                $dataObject = new DataObject\TeamMember();
                $dataObject->setClassName('TeamMember');
                $dataObject->setKey(\Pimcore\Model\Element\Service::getValidKey($memberName, 'object'));
                $dataObject->setParentId($folderId);

                // Set values based on Excel data
                $dataObject->setName($memberName);
                $dataObject->setNumber((float) $memberNumber);
                $dataObject->setDuty($memberDuty);
                $dataObject->setIsSubstitute($memberIsSubstitute);
                $dataObject->setAge((float) $memberAge);

                // Save and publish object
                $dataObject->setPublished(true);
                $dataObject->save();
                array_push($members, $dataObject);
            }
        };

        return $members;
    }

    function readInExcel($filename) {
        //  Create a new Xls Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

        // Tell the reader to only read the data. Ignore formatting etc.
        $reader->setReadDataOnly(true);

        // Read the spreadsheet file.
        $spreadsheet = $reader->load($filename);
        return $spreadsheet->getActiveSheet();
    }
}
