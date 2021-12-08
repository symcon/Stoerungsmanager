<?php

declare(strict_types=1);

class Stoerungsanzeige extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyString('VariableList', '[]');

        //Profiles
        if (!IPS_VariableProfileExists('STA.Confirm')) {
            IPS_CreateVariableProfile('STA.Confirm', 1);
            IPS_SetVariableProfileValues('STA.Confirm', 0, 2, 1);
            IPS_SetVariableProfileAssociation('STA.Confirm', 0, $this->Translate('Need for Action'), 'IPS', 0xFF0000);
            IPS_SetVariableProfileAssociation('STA.Confirm', 1, $this->Translate('Work in progress'), 'HollowArrowUp', 0xFFFF00);
            IPS_SetVariableProfileAssociation('STA.Confirm', 2, $this->Translate('All right'), 'IPS', 0x80FF80);
        }
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        //Creating array containing variable IDs in List
        $variableIDs = [];
        $variableList = json_decode($this->ReadPropertyString('VariableList'), true);
        foreach ($variableList as $line) {
            $variableIDs[] = $line['VariableID'];
        }

        //Creating links and variable for all variable IDs in VariableList
        foreach ($variableList as $line) {
            $variableID = $line['VariableID'];

            $this->RegisterMessage($variableID, VM_UPDATE);
            $this->RegisterReference($variableID);

            if ($line['Solution'] == 0) {
                if (!@$this->GetIDForIdent('Link' . $variableID)) {

                //Create links for variables
                    $linkID = IPS_CreateLink();
                    IPS_SetParent($linkID, $this->InstanceID);
                    IPS_SetLinkTargetID($linkID, $variableID);
                    IPS_SetIdent($linkID, 'Link' . $variableID);

                    //Setting initial visibility
                    IPS_SetHidden($linkID, (GetValue($variableID) == $this->GetSwitchValue($variableID)));
                }
            } elseif (GetValue($variableID) != $this->GetSwitchValue($variableID) && $line['Solution'] != 0) {
                //Create Variable if the status is !=2 and Solution is not 0
                $statusVariableID = $this->RegisterVariableInteger($line['VariableID'] . 'Status', IPS_GetName($line['VariableID']) . '-Status', 'STA.Confirm');
                // Set initial value
                $this->SetValue($line['VariableID'] . 'Status', 0);

                IPS_SetParent($statusVariableID, $this->InstanceID);
                $this->EnableAction($line['VariableID'] . 'Status');
                $this->RegisterMessage($statusVariableID, VM_UPDATE);
            }
        }

        //Deleting unlisted links
        foreach (IPS_GetChildrenIDs($this->InstanceID) as $linkID) {
            if (IPS_LinkExists($linkID)) {
                if (!in_array(IPS_GetLink($linkID)['TargetID'], $variableIDs)) {
                    $this->UnregisterMessage(IPS_GetLink($linkID)['TargetID'], VM_UPDATE);
                    $this->UnregisterReference(IPS_GetLink($linkID)['TargetID']);
                    $this->UnregisterReference($linkID);
                    IPS_DeleteLink($linkID);
                }
            } elseif (IPS_VariableExists($linkID)) {
                if (!in_array(substr(IPS_GetObject($linkID)['ObjectIdent'], 0, -strlen('Status')), $variableIDs)) {
                    $this->UnregisterMessage($linkID, VM_UPDATE);
                    $this->UnregisterVariable(IPS_GetObject($linkID)['ObjectIdent']);
                }
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        if ($Message == VM_UPDATE) {
            $variableList = json_decode($this->ReadPropertyString('VariableList'), true);
            if (in_array($SenderID, array_column($variableList, 'VariableID'))) {
                foreach ($variableList as $line) {
                    if ($line['VariableID'] == $SenderID) {
                        $solution = $line['Solution'];
                    }
                }
                //Is the variable off?
                if ($Data[0] == $this->GetSwitchValue($SenderID)) {
                    //Variable is off
                    if ($solution == 0) {
                        var_dump('Hide it');
                        IPS_SetHidden($this->GetIDForIdent('Link' . $SenderID), true);
                    } elseif ($solution == 2 && $this->GetValue($SenderID . 'Status') == 1) {
                        $this->SetValue($SenderID . 'Status', 2);
                        $this->UnregisterVariable($SenderID . 'Status');
                    }
                } else {
                    //Variable is on
                    if ($solution == 0) {
                        var_dump('I want to see it');
                        IPS_SetHidden($this->GetIDForIdent('Link' . $SenderID), false);
                    } elseif (IPS_VariableExists($SenderID . 'Status')) {
                        $statusVariableID = $this->RegisterVariableInteger($SenderID . 'Status', IPS_GetName($SenderID) . '-Status', 'STA.Confirm');
                        $this->EnableAction($SenderID . 'Status');
                        $this->RegisterMessage($statusVariableID, VM_UPDATE);
                        $this->SetValue($SenderID . 'Status', 0);
                    } else {
                        $this->SetValue($SenderID . 'Status', 0);
                    }
                }
            }
        }
    }

    public function RequestAction($id, $value)
    {
        if ($value != 0) {
            //Get informations (ID of the listen variable, how the change should handle)
            $variableList = json_decode($this->ReadPropertyString('VariableList'), true);
            $listenID = substr($id, 0, -strlen('Status'));
            foreach ($variableList as $line) {
                if ($line['VariableID'] == $listenID) {
                    $solution = $line['Solution'];
                }
            }

            //Handling of the Change
            if ($solution == 1) {
                $this->SetValue($id, 2);
            } elseif ($solution == 2) {
                if (GetValue($listenID) == $this->GetSwitchValue($listenID)) {
                    $this->SetValue($id, 2);
                } else {
                    $this->SetValue($id, 1);
                }
            }
        }

        //Unregister a variable if the status is all right
        if ($this->GetValue($id) == 2) {
            $this->UnregisterVariable($id);
        }
    }

    private function GetSwitchValue($VariableID)
    {
        //Return the value corresponding to the variable type.
        switch (IPS_GetVariable($VariableID)['VariableType']) {
            //Boolean
            case 0:
                return $this->IsProfileInverted($VariableID);
            //Integer
            case 1:

            //Float
            case 2:
                if (IPS_VariableProfileExists($this->GetVariableProfile($VariableID))) {
                    if ($this->IsProfileInverted($VariableID)) {
                        return IPS_GetVariableProfile($this->GetVariableProfile($VariableID))['MaxValue'];
                    } else {
                        return IPS_GetVariableProfile($this->GetVariableProfile($VariableID))['MinValue'];
                    }
                } else {
                    return 0;
                }

            //Integer
            // no break
            // FIXME: No break. Please add proper comment if intentional
            case 3:
                return '';

        }
    }

    private function GetVariableProfile($VariableID)
    {
        $variableProfileName = IPS_GetVariable($VariableID)['VariableCustomProfile'];
        if ($variableProfileName == '') {
            $variableProfileName = IPS_GetVariable($VariableID)['VariableProfile'];
        }
        return $variableProfileName;
    }

    private function IsProfileInverted($VariableID)
    {
        return substr($this->GetVariableProfile($VariableID), -strlen('.Reversed')) === '.Reversed';
    }
}
