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
            IPS_SetVariableProfileValues('STA.Confirm', 0, 2, 0);
            IPS_SetVariableProfileAssociation('STA.Confirm', 0, $this->Translate('Need for Action'), 'IPS', 0xFF0000);
            IPS_SetVariableProfileAssociation('STA.Confirm', 1, $this->Translate('Work in progress'), 'HollowArrowUp', 0xFFFF00);
            IPS_SetVariableProfileAssociation('STA.Confirm', 2, $this->Translate('All right'), 'IPS', 0x80FF80);
        }

        if (!IPS_VariableProfileExists('STA.ConfirmHide')) {
            IPS_CreateVariableProfile('STA.ConfirmHide', 1);
            IPS_SetVariableProfileValues('STA.ConfirmHide', 0, 2, 0);
            IPS_SetVariableProfileAssociation('STA.ConfirmHide', 0, $this->Translate('Need for Action'), 'IPS', 0xFF0000);
            IPS_SetVariableProfileAssociation('STA.ConfirmHide', 1, $this->Translate('Work in progress'), 'HollowArrowUp', 0xFFFF00);
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

            //One of the Checkboxes must be selected
            if (!$line['Confirmation'] && !$line['Hide']) {
                $this->SetStatus(200);
                return;
            } else {
                $this->SetStatus(102);
            }

            $this->RegisterMessage($variableID, VM_UPDATE);
            $this->RegisterReference($variableID);

            //If an option is change,  delete unused  or change it
            if (!$line['Confirmation'] && $line['Hide'] && @$this->GetIDForIdent($variableID . 'Status')) {
                //Unregister Variable if exist
                $this->UnregisterMessage($this->GetIDForIdent($variableID . 'Status'), VM_UPDATE);
                $this->UnregisterVariable($variableID . 'Status');
            } elseif ($line['Confirmation']) {
                //Delete Link if exist
                if (@$this->GetIDForIdent('Link' . $variableID)) {
                    $linkID = $this->GetIDForIdent('Link' . $variableID);
                    $this->UnregisterReference(IPS_GetLink($linkID)['TargetID']);
                    $this->UnregisterReference($linkID);
                    IPS_DeleteLink($this->GetIDForIdent('Link' . $variableID));
                }

                //Change Profile if necessary
                $profile = $this->GetVariableProfile($this->GetIDForIdent($variableID . 'Status'));
                if ($line['Hide'] && $profile != 'STA.ConfirmHide') {
                    $this->RegisterVariableInteger($variableID . 'Status', IPS_GetName($variableID) . '-Status', 'STA.ConfirmHide');
                } else {
                    $this->RegisterVariableInteger($variableID . 'Status', IPS_GetName($variableID) . '-Status', 'STA.Confirm');
                }
            }

            //Create links or variable if necessary
            if (!$line['Confirmation'] && $line['Hide']) {
                if (!@$this->GetIDForIdent('Link' . $variableID)) {
                    //Create links for variables
                    $linkID = IPS_CreateLink();
                    IPS_SetParent($linkID, $this->InstanceID);
                    IPS_SetLinkTargetID($linkID, $variableID);
                    IPS_SetIdent($linkID, 'Link' . $variableID);

                    //Setting initial visibility
                    IPS_SetHidden($linkID, (GetValue($variableID) == $this->GetSwitchValue($variableID)));
                }
            } elseif (GetValue($variableID) != $this->GetSwitchValue($variableID) && $line['Confirmation']) {

                //Get profile name
                if ($line['Hide']) {
                    $profile = 'STA.ConfirmHide';
                } else {
                    $profile = 'STA.Confirm';
                }

                //Create Variable
                $statusVariableID = $this->RegisterVariableInteger($variableID . 'Status', IPS_GetName($variableID) . '-Status', $profile);
                IPS_SetParent($statusVariableID, $this->InstanceID);
                $this->EnableAction($variableID . 'Status');
                $this->RegisterMessage($statusVariableID, VM_UPDATE);

                // Set initial value
                $this->SetValue($variableID . 'Status', 0);
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
            $line = array_search($SenderID, array_column($variableList, 'VariableID'));
            if ($line !== false) {
                $line = $variableList[$line];
                //Is the variable off?
                if ($Data[0] == $this->GetSwitchValue($SenderID)) {
                    //Variable is off
                    if ($line['Hide'] && !$line['Confirmation']) {
                        IPS_SetHidden($this->GetIDForIdent('Link' . $SenderID), true);
                        if ($line['Notification'] != 0) {
                            BN_Reset($line['Notification']);
                        }
                    } elseif ($line['Confirmation'] && (
                        ($line['Hide'] && $this->GetValue($SenderID . 'Status') == 1) ||
                        (!$line['Hide'] && $this->GetValue($SenderID . 'Status') == 2))) {
                        $this->UnregisterVariable($SenderID . 'Status');
                        if ($line['Notification'] != 0) {
                            BN_Reset($line['Notification']);
                            //Get ID of Active Variable of notification
                        }
                    }
                } else {
                    //Variable is on
                    if ($line['Hide'] && !$line['Confirmation']) {
                        IPS_SetHidden($this->GetIDForIdent('Link' . $SenderID), false);
                    } elseif (IPS_VariableExists($SenderID . 'Status')) {
                        $statusVariableID = $this->RegisterVariableInteger($SenderID . 'Status', IPS_GetName($SenderID) . '-Status', $line['Hide'] ? 'STA.ConfirmHide' : 'STA.Confirm');
                        $this->EnableAction($SenderID . 'Status');
                        $this->RegisterMessage($statusVariableID, VM_UPDATE);
                        $this->SetValue($SenderID . 'Status', 0);
                        if ($line['Notification'] != 0) {
                            BN_Reset($line['Notification']);
                        }
                    } else {
                        $this->SetValue($SenderID . 'Status', 0);
                        if ($line['Notification'] != 0) {
                            BN_Reset($line['Notification']);
                        }
                    }
                    if ($line['Notification'] != 0) {
                        BN_IncreaseLevel($line['Notification']);
                    }
                }
            }
        }
    }

    public function RequestAction($id, $value)
    {
        if ($value != 0) {
            //Conformation is on
            //Get information's (ID of the listen variable, how the change should handle)
            $variableList = json_decode($this->ReadPropertyString('VariableList'), true);
            $listenId = substr($id, 0, -strlen('Status'));
            $line = $variableList[array_search($listenId, array_column($variableList, 'VariableID'))];

            //Handling
            if ((GetValue($listenId) == $this->GetSwitchValue($listenId) && $line['Hide'] && $value == 1) ||
                (!$line['Hide'] && $value == 2)) {
                $this->UnregisterVariable($id);
            } else {
                $this->SetValue($id, $value);
            }

            if ($line['Notification'] != 0) {
                BN_Reset($line['Notification']);
            }
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
