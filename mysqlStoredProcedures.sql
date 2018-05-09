-- MySQL dump 10.13  Distrib 5.7.17, for Win64 (x86_64)
--
-- Host: NETDRV03    Database: axiotek_voamapping
-- ------------------------------------------------------
-- Server version	5.5.57-MariaDB


-- -------------------------------------------------------------------------------------------------------------------------------------
DELIMITER ;;
CREATE DEFINER=`tjohnson`@`%` PROCEDURE `CreateJob`(IN sJobName varchar(255), IN slogfile varchar(255), IN sStatus varchar(45), IN sDataSource varchar(45), IN sFiletype varchar(10))
BEGIN

/*COMMENTS
CREATED: TJohnson, 20180226

PURPOSE: insert new job into the jobs table.  
NOTES: 
to debug, uncomment the select statements

PARAMETERS: always use sjobname, slogfile and sStatus

REVISIONS and MODS HERE:
DA - added sDataSource and sFiletype, march 2018

*/
-- SET startdate = NOW();

INSERT INTO `axiotek_voamapping`.`ConversionJobs`
(`JobName`, `StartDateTime`, `LogFileName`, `Status`, `AffiliateDataSource`, `StartingFileType`)
 VALUES (sJobName, Now(), slogfile, sStatus, sDataSource, sFiletype);

END ;;
DELIMITER ;
-- -------------------------------------------------------------------------------------------------------------------------------------
-- -------------------------------------------------------------------------------------------------------------------------------------
DELIMITER ;;
CREATE DEFINER=`datkinson`@`%` PROCEDURE `PopulateSyncPointIDsTable`(IN sjobname varchar(255), sBuildingColumnName varchar(255), sDataSource varchar(255))
BEGIN

/*COMMENTS
CREATED: DAtkinson, 20180313

PURPOSE: Put info from the .xls file into the static syncpointIDs table. After this, run
the deduping procedure.
NOTES: 

PARAMETERS: always use sjobname, sBuildingColumnName, sDataSource

REVISIONS and MODS HERE:

*/

SET @jobID = (SELECT JobID FROM ConversionJobs WHERE JobName = sJobname);
SET @sjobname = sjobname;
SET @sDataSource = sDataSource;

-- CLIENTS
-- put certain columns from Intake table into SyncPointIDs table 
-- still need to make Intake a tablename parameter.
INSERT INTO SyncPointIDs (JobID, JobName, TagType, ClientUID, ClientUIDen)
SELECT @jobID, @sjobname, 'Client', UUID, UUIDen FROM axiotek_voamapping.Intake; -- Where not null?
-- SELECT UUID, UUIDen, (SELECT JobID from ConversionJobs WHERE JobName = sjobname), sjobname FROM Intake;

-- PROVIDERS
-- put certain columns from Intake table into SyncPointIDs table 
-- INSERT INTO SyncPointIDs (JobID, JobName, TagType, ClientUID)
-- SELECT DISTINCT @jobID, sjobname, 'Provider', `Building Name-1` FROM axiotek_voamapping.Intake; -- Where not null?

set @sql_text01 = 'INSERT INTO SyncPointIDs (JobID, JobName, TagType, ClientUID) ';
set @sql_text02 = concat(@sql_text01, 'SELECT DISTINCT @jobID, @sjobname, "Provider", concat(@sDataSource,`');
set @sql_text03 = concat(@sql_text02, sBuildingColumnName);
set @sql_text04 = concat(@sql_text03, '`) FROM axiotek_voamapping.Intake;');
prepare statement01 from @sql_text04;
execute statement01;
deallocate prepare statement01;

-- set systemID equal to the value in the primary key column
UPDATE SyncPointIDs SET SystemID = ID WHERE `JobID` = @jobID;


END ;;
DELIMITER ;
-- -------------------------------------------------------------------------------------------------------------------------------------
-- -------------------------------------------------------------------------------------------------------------------------------------
DELIMITER ;;
CREATE DEFINER=`datkinson`@`%` PROCEDURE `PutDistinctValuesIntoNewTable`(IN tablename varchar(50), IN columnname varchar(50))
BEGIN

/*COMMENTS
CREATED: DAtkinson, 20180301

PURPOSE: Generate a table containing all the distinct values found in the input column.
NOTES: to debug, uncomment the select statements
Currently used on a table with only one column. If used for multiple columns in one table, the
naming of the new table would have to be revised, perhaps by including the original columnname.

PARAMETERS: always use tablename, columnname

REVISIONS and MODS HERE:

*/

-- Create table
set @newTablename = concat('Distinct', tablename);
CALL `axiotek_voamapping`.`TableDrop`(@newTablename);

set @sql_text01 = concat('CREATE TABLE ', @newTablename, ' (`ID` int(11) NOT NULL AUTO_INCREMENT,`');
set @sql_text02 = concat(columnname, '` varchar(250) DEFAULT NULL, PRIMARY KEY (`ID`))');
set @sql_text = concat(@sql_text01, @sql_text02);

-- SELECT @sql_text as sqlStatement, @newTablename as newtable;

prepare statement01 from @sql_text;
execute statement01;
deallocate prepare statement01;


-- Insert distinct data
set @sql_text01 = concat('INSERT INTO ', @newTablename, ' (', columnname, ') ');
set @sql_text02 = concat('SELECT DISTINCT Path FROM ', tablename);
set @sql_text = concat(@sql_text01, @sql_text02);

-- SELECT @sql_text as sqlStatement, @newTablename as newtable;

prepare statement01 from @sql_text;
execute statement01;
deallocate prepare statement01;

END ;;
DELIMITER ;
-- -------------------------------------------------------------------------------------------------------------------------------------
-- -------------------------------------------------------------------------------------------------------------------------------------
DELIMITER ;;
CREATE DEFINER=`datkinson`@`%` PROCEDURE `setProviderUUIDtoNewNames`(IN sjobname varchar(255), sBuildingColumnName varchar(255), sDataSource varchar(255))
BEGIN

-- DA 20180426
-- uses a series of temp tables to find the new provider names.
-- replaces them in the system table for the provider records of the current job.
-- executes the 2nd pass dedupe for providers.

SET @jobID = (SELECT JobID FROM ConversionJobs WHERE JobName = sJobname);
SET @sjobname = sjobname;
SET @sDataSource = sDataSource;

DROP TABLE IF EXISTS xlsFileTempUUID;
DROP TABLE IF EXISTS xlsProviderNameWithSystemID;
DROP TABLE IF EXISTS xlsProviderNameWithAIIDandVOANLID;
DROP TABLE IF EXISTS xlsProviderNameWithVOANLname;
    
-- grab old provider names from file.
-- CREATE TEMPORARY TABLE IF NOT EXISTS xlsFileTempUUID AS    
-- (SELECT DISTINCT @sDataSource AS dataSource, @sBuildingColumnName AS smProviderName, concat("NJDV",`Building Name-1`) as TEMP_UUID 
-- FROM axiotek_voamapping.Intake);

set @sql_text01 = 'CREATE TEMPORARY TABLE IF NOT EXISTS xlsFileTempUUID AS ';
set @sql_text02 = concat(@sql_text01, '(SELECT DISTINCT @sDataSource AS dataSource, `');
set @sql_text03 = concat(@sql_text02, sBuildingColumnName);
set @sql_text04 = concat(@sql_text03, '` AS smProviderName, concat(@sDataSource,`');
set @sql_text05 = concat(@sql_text04, sBuildingColumnName);
set @sql_text06 = concat(@sql_text05, '`) as TEMP_UUID ');
set @sql_text07 = concat(@sql_text06, 'FROM axiotek_voamapping.Intake);');
prepare statement01 from @sql_text07;
execute statement01;
deallocate prepare statement01;
select * from xlsFileTempUUID;

-- grab systemID with join from xlsFileTempUUID on provider UUID
CREATE TEMPORARY TABLE IF NOT EXISTS xlsProviderNameWithSystemID AS  
(select DISTINCT dataSource, smProviderName, TEMP_UUID, SystemID 
from xlsFileTempUUID left JOIN SyncPointIDs on TEMP_UUID=ClientUID);
select * from xlsProviderNameWithSystemID;

-- get AIID from ProviderIDS and voanlID (ControlValue) from Specifications.
CREATE TEMPORARY TABLE IF NOT EXISTS xlsProviderNameWithAIIDandVOANLID AS  
(select DISTINCT dataSource, smProviderName, TEMP_UUID, SystemID, ProviderIDs.ID AS AAID, Specifications.ControlValue
from xlsProviderNameWithSystemID left JOIN ProviderIDs on smProviderName=SecurManageName left Join Specifications on ProviderIDs.ID = Specifications.ControlLabel
 WHERE Specifications.SpecName = 'NJDV_ProviderMap_withXMLmap');
select * from xlsProviderNameWithAIIDandVOANLID;

-- get new provider name from ProviderIDs
CREATE TEMPORARY TABLE IF NOT EXISTS xlsProviderNameWithVOANLname AS  
(select DISTINCT dataSource, smProviderName, TEMP_UUID, SystemID, AAID, ControlValue, NewProviderName 
from xlsProviderNameWithAIIDandVOANLID left JOIN ProviderIDs on ControlValue=ProviderID);
select * from xlsProviderNameWithVOANLname;

-- update UUID in system table. Uses the two previous temp tables.
 UPDATE SyncPointIDs SET ClientUID = 
 (SELECT concat(dataSource,NewProviderName) FROM xlsProviderNameWithVOANLname WHERE TEMP_UUID = ClientUID)
 WHERE TagType = 'Provider' And ClientUID IN (SELECT TEMP_UUID from xlsProviderNameWithAIIDandVOANLID);

-- 2nd pass dedupe for providers
 CALL `axiotek_voamapping`.`SetSyncPointIDsForDuplicateRecords`('Provider');
 
END ;;
DELIMITER ;
-- -------------------------------------------------------------------------------------------------------------------------------------
-- -------------------------------------------------------------------------------------------------------------------------------------
DELIMITER ;;
CREATE DEFINER=`datkinson`@`%` PROCEDURE `SetSyncPointIDsForDuplicateClients`()
BEGIN

/*COMMENTS
CREATED: DAtkinson, 20180315

PURPOSE: Uses the ClientUID field to set the syncpoint IDs the same for duplicate clients.
NOTES: 

PARAMETERS: 

REVISIONS and MODS HERE:

*/

DROP TABLE IF EXISTS MinIDsTable;

CREATE TEMPORARY TABLE IF NOT EXISTS MinIDsTable AS 
(SELECT  ClientUID AS TEMP_UUID, MIN(SystemID) AS TEMP_MinSystemID
FROM    SyncPointIDs SP1
WHERE   EXISTS
        (
        SELECT  1
        FROM    SyncPointIDs SP2
        WHERE   SP2.ClientUID = SP1.ClientUID
        LIMIT 1, 1
        ) GROUP BY ClientUID ORDER BY ClientUID);

DROP TABLE IF EXISTS ColumnOfDupeClients;       
 
CREATE TEMPORARY TABLE IF NOT EXISTS ColumnOfDupeClients AS    
(SELECT TEMP_UUID FROM MinIDsTable);   
        
UPDATE SyncPointIDs SET SystemID = (SELECT TEMP_MinSystemID FROM MinIDsTable WHERE TEMP_UUID = ClientUID)
WHERE ClientUID IN (SELECT TEMP_UUID from ColumnOfDupeClients);


END ;;
DELIMITER ;
-- -------------------------------------------------------------------------------------------------------------------------------------
-- -------------------------------------------------------------------------------------------------------------------------------------
DELIMITER ;;
CREATE DEFINER=`datkinson`@`%` PROCEDURE `SetSyncPointIDsForDuplicateRecords`(IN sTagType varchar(255))
BEGIN

/*COMMENTS
CREATED: DAtkinson, 20180315

PURPOSE: 
NOTES: 
PARAMETERS: always use sTagType

REVISIONS and MODS HERE:
20180425 -  modified to handle records other than clients.

*/

SET @sTagType = sTagType;

-- determine minimum systemID for every record with a duplicate UUID
DROP TABLE IF EXISTS MinIDsTable;
CREATE TEMPORARY TABLE IF NOT EXISTS MinIDsTable AS 
(SELECT  ClientUID AS TEMP_UUID, MIN(SystemID) AS TEMP_MinSystemID
FROM    SyncPointIDs SP1
WHERE TagType = @sTagType AND  EXISTS
        (
        SELECT  1
        FROM    SyncPointIDs SP2
        WHERE   SP2.ClientUID = SP1.ClientUID
        LIMIT 1, 1
        ) GROUP BY ClientUID ORDER BY ClientUID);
        
 -- get column
DROP TABLE IF EXISTS ColumnOfDupeUUIDs;
CREATE TEMPORARY TABLE IF NOT EXISTS ColumnOfDupeUUIDs AS    
(SELECT TEMP_UUID FROM MinIDsTable);   

-- Update the system table
UPDATE SyncPointIDs SET SystemID = (SELECT TEMP_MinSystemID FROM MinIDsTable WHERE TEMP_UUID = ClientUID)
WHERE ClientUID IN (SELECT TEMP_UUID from ColumnOfDupeUUIDs);
END ;;
DELIMITER ;
-- -------------------------------------------------------------------------------------------------------------------------------------
-- -------------------------------------------------------------------------------------------------------------------------------------
DELIMITER ;;
CREATE DEFINER=`tjohnson`@`%` PROCEDURE `TableCleanup`(IN tablename varchar(50), IN bWhere boolean, IN fieldname varchar(50), IN fieldvalue varchar(50))
BEGIN

/*COMMENTS
CREATED: TJohnson, 20180104
PURPOSE: allow for deletion of all records or by where clause of varchar field in any table
NOTES: Improvements to be made include error handling and requirement of parameters
to debug, uncomment the select statements
PARAMETERS: always use tablename and bWhere, use Null for fieldname and field value if bWhere is FALSE
REVISIONS and MODS HERE:

*/

	set @bval = bWhere;
    set @fval = fieldname;
    set @vval = fieldvalue;
    
    IF Not @bval THEN 
		
        -- set @sql_text01 = concat('Select * from ', tablename);
        set @sql_text02 = concat('Delete from ', tablename); 
        
        -- Select bWhere, tablename, @sql_text01, @sql_text02;
        
    ELSEIF @bval THEN
    
		-- set @sql_text01 = concat('Select * from ', tablename, ' WHERE ', fieldname, ' = "', fieldvalue, '"');
        set @sql_text02 = concat('Delete from ', tablename, ' WHERE ', fieldname,  ' = "' , fieldvalue, '"'); 
        
		-- Select bWhere, fieldname, @sql_text01, @sql_text02;
        
    END IF;
    
    /*OTHER EXAMPLE: set @sql_text = concat('Select * from ', tablename, ' WHERE id = ?');*/
    
    /*prepare statement01 from @sql_text01;
    execute statement01;
    deallocate prepare statement01;*/
    
    prepare statement02 from @sql_text02;
    execute statement02;
    SELECT ROW_COUNT();
    deallocate prepare statement02;
    
    
END ;;
DELIMITER ;
-- -------------------------------------------------------------------------------------------------------------------------------------
-- -------------------------------------------------------------------------------------------------------------------------------------
DELIMITER ;;
CREATE DEFINER=`datkinson`@`%` PROCEDURE `TableDrop`(IN tablename CHAR(50))
BEGIN

/*COMMENTS
CREATED: TJohnson, 20180104
PURPOSE: allow for dropping of any table
NOTES: Improvements to be made include error handling and requirement of parameters
to debug, uncomment the select statement
PARAMETERS: always use tablename, no other parameters
REVISIONS and MODS HERE:

*/

set @sql_text = concat('DROP TABLE IF EXISTS ', tablename); 

-- Select tablename, @sql_text;

prepare statement from @sql_text;
execute statement;
deallocate prepare statement;

END ;;
DELIMITER ;
-- -------------------------------------------------------------------------------------------------------------------------------------
-- -------------------------------------------------------------------------------------------------------------------------------------
DELIMITER ;;
CREATE DEFINER=`tjohnson`@`%` PROCEDURE `UpdateIntake`(IN tablename varchar(50), IN sKey varchar(50), IN bEncryptOnly boolean)
BEGIN

/*COMMENTS
CREATED: TJohnson, 20180214

PURPOSE: create firstname, lastname and uuid fields in intake if none exist, and create encryption uniqueID for dup checking
NOTES: Improvements to be made include error handling and requirement of parameters
to debug, uncomment the select statements

PARAMETERS: always use tablename, skey and bWhere

REVISIONS and MODS HERE:
DA 20180312 - FN, LN, and UUID are now in separate IF clauses.

*/

DECLARE istblthere BOOLEAN;
DECLARE isUUIDthere BOOLEAN;
DECLARE isFNthere BOOLEAN;
DECLARE isLNthere BOOLEAN;
-- DECLARE fsucceeded BOOLEAN;

SET istblthere = TableExists ( 'axiotek_voamapping', tablename );
SET isUUIDthere = FieldExists ( 'axiotek_voamapping', tablename, 'UUID' );
SET isFNthere = FieldExists ( 'axiotek_voamapping', tablename, 'First Name' );
SET isLNthere = FieldExists ( 'axiotek_voamapping', tablename, 'Last Name' );

Select bEncryptOnly, tablename, isUUIDthere, isFNthere, isLNthere;
 
IF istblthere THEN

	IF Not bEncryptOnly THEN 
		
        IF Not isFNthere THEN -- create fields
        
			set @sql_text01 = concat('ALTER TABLE ', tablename);
			set @sql_text02 = concat(@sql_text01, ' ADD COLUMN `First Name` VARCHAR(255) NULL;');
			prepare statement01 from @sql_text02;
			execute statement01;
			deallocate prepare statement01;
            
            -- SELECT @sql_text02 as p01;
        
        END IF;
        
        IF Not isLNthere THEN -- create fields
        
			set @sql_text01 = concat('ALTER TABLE ', tablename);
			set @sql_text02 = concat(@sql_text01, ' ADD COLUMN `Last Name` VARCHAR(255) NULL;');
			prepare statement01 from @sql_text02;
			execute statement01;
			deallocate prepare statement01;
            
            -- SELECT @sql_text02 as p01;
        
        END IF;
        
        IF Not isUUIDthere THEN -- create fields
        
			set @sql_text01 = concat('ALTER TABLE ', tablename);
			set @sql_text02 = concat(@sql_text01, ' ADD COLUMN `UUID` VARCHAR(255) NULL, ADD COLUMN `UUIDen` BLOB NULL;');
			prepare statement01 from @sql_text02;
			execute statement01;
			deallocate prepare statement01;
            
            -- SELECT @sql_text02 as p01;
        
        END IF;
        
        -- update uuid with string
        set @sql_text01 = concat('UPDATE ', tablename);
        set @sql_text02 = concat(@sql_text01, ' SET `UUID` = concat(If(`SM ID` = "","99999",`SM ID`), "*", If(`RegNum` = "","99999-999",`RegNum`), "*", STR_TO_DATE(`DateofBirth`, "%c/%d/%y"));');
        
        prepare statement01 from @sql_text02;
		execute statement01;
		deallocate prepare statement01;
        
		-- SELECT @sql_text02 as p02;
        
	END IF;
        
    -- run encryption with prgm key
    set @sql_text01 = concat('UPDATE ', tablename);
    set @sql_text02 = concat(@sql_text01, ' SET `UUIDen` = aes_encrypt(`UUID`, "', sKey, '");');
        
	prepare statement01 from @sql_text02;
	execute statement01;
	deallocate prepare statement01;
    
    SELECT @sql_text02 as p03;
        
      
END IF;

END ;;
DELIMITER ;
-- -------------------------------------------------------------------------------------------------------------------------------------
-- -------------------------------------------------------------------------------------------------------------------------------------
DELIMITER ;;
CREATE DEFINER=`tjohnson`@`%` PROCEDURE `UpdateJobByID`(IN iJobID int(11), IN sStatus varchar(45))
BEGIN
/*COMMENTS
CREATED: TJohnson, 20180226

PURPOSE: Modify job in the jobs table. 
Also modify corresponding records in the SyncPointIDs table.  
NOTES: 

PARAMETERS: sJobname and sStatus

Sample procedure calls:
CALL `axiotek_voamapping`.`UpdateJobByID`(7, 'Imported');
CALL `axiotek_voamapping`.`UpdateJobByID`(22, 'Deleted');

REVISIONS and MODS HERE:
DA 20180316 - modified to include conditional logic and SyncPointIDs table.
DA 20180320 - takes jobID as input instead of jobName

*/
SET @updateDate = NOW();

IF sStatus = 'Imported' THEN

	UPDATE `axiotek_voamapping`.`ConversionJobs`
	SET `EndDateTime` = @updateDate,
	`Status` = sStatus,
	`Errors` = 'unknown'
	WHERE `JobID` = iJobID;

	UPDATE `axiotek_voamapping`.`SyncPointIDs`
	SET `DateMarkedImported` = @updateDate,
	`Imported` = 1
	WHERE `JobID` = iJobID;

END IF;

IF sStatus = 'Deleted' THEN

	UPDATE `axiotek_voamapping`.`ConversionJobs`
	SET `EndDateTime` = @updateDate,
	`Status` = sStatus,
	`Errors` = 'unknown'
	WHERE `JobID` = iJobID;

	DELETE FROM `axiotek_voamapping`.`SyncPointIDs`
	WHERE `JobID` = iJobID;

END IF;

END ;;
DELIMITER ;
-- -------------------------------------------------------------------------------------------------------------------------------------

-- Dump completed on 2018-05-09 16:02:19
