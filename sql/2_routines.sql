-- File 2: stored function, procedures (with cursor) and triggers
-- (MySQL's version of PL/SQL)
USE evac_db;

DROP FUNCTION IF EXISTS fn_beds_left;
DROP PROCEDURE IF EXISTS sp_assign_victim;
DROP PROCEDURE IF EXISTS sp_complete_dispatch;
DROP PROCEDURE IF EXISTS sp_facility_report;
DROP TRIGGER IF EXISTS trg_transport_check;
DROP TRIGGER IF EXISTS trg_dispatch_check;

DELIMITER $$

-- 1. Function: beds (or shelter places) left in a facility
CREATE FUNCTION fn_beds_left(p_fid INT) RETURNS INT
READS SQL DATA
BEGIN
    DECLARE total INT DEFAULT 0;
    DECLARE used INT DEFAULT 0;

    SELECT COALESCE(
        (SELECT Total_Beds FROM Hospital WHERE Facility_ID = p_fid),
        (SELECT Capacity FROM Shelter WHERE Facility_ID = p_fid),
        0) INTO total;

    SELECT COUNT(*) INTO used FROM Transport WHERE Facility_ID = p_fid;

    RETURN total - used;
END$$

-- 2. Procedure: put a victim on a dispatch and send them to a facility
CREATE PROCEDURE sp_assign_victim(IN p_dispatch INT, IN p_victim INT, IN p_facility INT)
BEGIN
    INSERT INTO Transport (Dispatch_ID, Victim_ID, Facility_ID, Pickup_Time)
    VALUES (p_dispatch, p_victim, p_facility, NOW());
END$$

-- 3. Procedure: finish a dispatch
CREATE PROCEDURE sp_complete_dispatch(IN p_dispatch INT)
BEGIN
    UPDATE Transport SET Dropoff_Time = NOW()
    WHERE Dispatch_ID = p_dispatch AND Dropoff_Time IS NULL;

    UPDATE Dispatch SET Status = 'Completed', Arrival_Time = NOW()
    WHERE Dispatch_ID = p_dispatch;
END$$

-- 4. Procedure with a cursor: capacity report for all facilities
CREATE PROCEDURE sp_facility_report()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE f_id INT;
    DECLARE f_name VARCHAR(80);
    DECLARE f_type VARCHAR(20);
    DECLARE places_left INT;
    DECLARE msg VARCHAR(20);

    DECLARE cur CURSOR FOR SELECT Facility_ID, Name, Type FROM Facility;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    DROP TEMPORARY TABLE IF EXISTS tmp_report;
    CREATE TEMPORARY TABLE tmp_report (
        Facility_ID INT, Name VARCHAR(80), Type VARCHAR(20),
        Places_Left INT, Status_Msg VARCHAR(20)
    );

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO f_id, f_name, f_type;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        SET places_left = fn_beds_left(f_id);

        IF places_left <= 0 THEN
            SET msg = 'FULL';
        ELSEIF places_left <= 2 THEN
            SET msg = 'ALMOST FULL';
        ELSE
            SET msg = 'AVAILABLE';
        END IF;

        INSERT INTO tmp_report VALUES (f_id, f_name, f_type, places_left, msg);
    END LOOP;
    CLOSE cur;

    SELECT * FROM tmp_report;
END$$

-- 5. Trigger: check before a victim is added to a transport
CREATE TRIGGER trg_transport_check BEFORE INSERT ON Transport
FOR EACH ROW
BEGIN
    DECLARE v_inc INT;
    DECLARE d_inc INT;

    SELECT Incident_ID INTO v_inc FROM Victim WHERE Victim_ID = NEW.Victim_ID;
    SELECT Incident_ID INTO d_inc FROM Dispatch WHERE Dispatch_ID = NEW.Dispatch_ID;

    IF v_inc <> d_inc THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This victim belongs to a different incident than the dispatch';
    END IF;

    IF fn_beds_left(NEW.Facility_ID) <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This facility has no beds / places left';
    END IF;
END$$

-- 6. Trigger: a vehicle or driver cannot be on two active dispatches
CREATE TRIGGER trg_dispatch_check BEFORE INSERT ON Dispatch
FOR EACH ROW
BEGIN
    IF NEW.Status IN ('Dispatched','En Route') THEN
        IF EXISTS (SELECT 1 FROM Dispatch
                   WHERE Vehicle_ID = NEW.Vehicle_ID AND Status IN ('Dispatched','En Route')) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'This vehicle is already on an active dispatch';
        END IF;
        IF EXISTS (SELECT 1 FROM Dispatch
                   WHERE Driver_ID = NEW.Driver_ID AND Status IN ('Dispatched','En Route')) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'This driver is already on an active dispatch';
        END IF;
    END IF;
END$$

DELIMITER ;
