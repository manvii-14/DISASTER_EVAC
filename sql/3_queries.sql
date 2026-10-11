-- File 3: the 12 queries used in the report (also shown on queries.php)
USE evac_db;

-- Q1: all incidents with the name of the reporter (join)
SELECT i.Incident_ID, i.Incident_Type, i.Severity_Level, i.Location, r.Name AS Reported_By, r.Contact_Number
FROM Incident i JOIN Reporter r ON i.Reporter_ID = r.Reporter_ID;

-- Q2: number of victims in each incident (group by)
SELECT i.Incident_ID, i.Incident_Type, COUNT(v.Victim_ID) AS Total_Victims
FROM Incident i LEFT JOIN Victim v ON i.Incident_ID = v.Incident_ID
GROUP BY i.Incident_ID, i.Incident_Type;

-- Q3: red priority victims with their incident
SELECT v.Victim_ID, v.Name, v.Age, i.Incident_Type, i.Location
FROM Victim v JOIN Incident i ON v.Incident_ID = i.Incident_ID
WHERE v.Priority_Level = 'Red';

-- Q4: vehicles that are free right now (subquery)
SELECT Vehicle_ID, Vehicle_Type, Reg_Number FROM Vehicle
WHERE Vehicle_ID NOT IN (SELECT Vehicle_ID FROM Dispatch WHERE Status IN ('Dispatched','En Route'));

-- Q5: drivers who are free right now
SELECT Driver_ID, Name, Experience_Years FROM Driver
WHERE Driver_ID NOT IN (SELECT Driver_ID FROM Dispatch WHERE Status IN ('Dispatched','En Route'));

-- Q6: beds left in each hospital
SELECT f.Name, h.Total_Beds, COUNT(t.Victim_ID) AS Occupied, h.Total_Beds - COUNT(t.Victim_ID) AS Beds_Left
FROM Hospital h JOIN Facility f ON h.Facility_ID = f.Facility_ID
LEFT JOIN Transport t ON t.Facility_ID = h.Facility_ID
GROUP BY f.Name, h.Total_Beds;

-- Q7: places left in each shelter (uses the stored function)
SELECT f.Name, s.Capacity, fn_beds_left(s.Facility_ID) AS Places_Left
FROM Shelter s JOIN Facility f ON s.Facility_ID = f.Facility_ID;

-- Q8: drivers with more experience than the average driver
SELECT Name, Experience_Years FROM Driver
WHERE Experience_Years > (SELECT AVG(Experience_Years) FROM Driver);

-- Q9: average trip time (minutes) for each vehicle type
SELECT v.Vehicle_Type, ROUND(AVG(TIMESTAMPDIFF(MINUTE, d.Dispatch_Time, d.Arrival_Time)), 1) AS Avg_Minutes
FROM Dispatch d JOIN Vehicle v ON d.Vehicle_ID = v.Vehicle_ID
WHERE d.Arrival_Time IS NOT NULL
GROUP BY v.Vehicle_Type;

-- Q10: full dispatch details (5 tables joined)
SELECT d.Dispatch_ID, i.Incident_Type, v.Reg_Number, dr.Name AS Driver, r.Start_Location, r.End_Location, r.Distance_KM, d.Status
FROM Dispatch d
JOIN Incident i ON d.Incident_ID = i.Incident_ID
JOIN Vehicle v ON d.Vehicle_ID = v.Vehicle_ID
JOIN Driver dr ON d.Driver_ID = dr.Driver_ID
JOIN Route r ON d.Route_ID = r.Route_ID;

-- Q11: which victim went to which facility
SELECT vi.Name AS Victim, vi.Priority_Level, f.Name AS Facility, f.Type, t.Pickup_Time, t.Dropoff_Time
FROM Transport t
JOIN Victim vi ON t.Victim_ID = vi.Victim_ID
JOIN Facility f ON t.Facility_ID = f.Facility_ID
ORDER BY t.Pickup_Time;

-- Q12: number of dispatches done by every driver (including 0)
SELECT dr.Name, COUNT(d.Dispatch_ID) AS Total_Dispatches
FROM Driver dr LEFT JOIN Dispatch d ON dr.Driver_ID = d.Driver_ID
GROUP BY dr.Driver_ID, dr.Name;

-- Q13: all vehicles with their subtype detail (union)
SELECT v.Vehicle_ID, v.Reg_Number, 'Ambulance' AS Subtype, a.Equip_Level AS Detail
FROM Vehicle v JOIN Ambulance a ON v.Vehicle_ID = a.Vehicle_ID
UNION
SELECT v.Vehicle_ID, v.Reg_Number, 'Evacuation Bus', CONCAT(b.Seat_Capacity, ' seats')
FROM Vehicle v JOIN Evacuation_Bus b ON v.Vehicle_ID = b.Vehicle_ID
UNION
SELECT v.Vehicle_ID, v.Reg_Number, 'Helicopter', CONCAT(h.Fuel_Capacity, ' L fuel')
FROM Vehicle v JOIN Helicopter h ON v.Vehicle_ID = h.Vehicle_ID;

-- PL/SQL part (stored routines):
-- CALL sp_facility_report();
-- CALL sp_assign_victim(6, 12, 1);
-- CALL sp_complete_dispatch(6);
