<?php
include "auth.php";
$page_title = "Queries";
include "header.php";

// title => sql
$queries = [
 "Q1. Incidents with the reporter name (join)" =>
  "SELECT i.Incident_ID, i.Incident_Type, i.Severity_Level, i.Location, r.Name AS Reported_By, r.Contact_Number
   FROM Incident i JOIN Reporter r ON i.Reporter_ID = r.Reporter_ID",

 "Q2. Number of victims in each incident (group by)" =>
  "SELECT i.Incident_ID, i.Incident_Type, COUNT(v.Victim_ID) AS Total_Victims
   FROM Incident i LEFT JOIN Victim v ON i.Incident_ID = v.Incident_ID
   GROUP BY i.Incident_ID, i.Incident_Type",

 "Q3. Red priority victims" =>
  "SELECT v.Victim_ID, v.Name, v.Age, i.Incident_Type, i.Location
   FROM Victim v JOIN Incident i ON v.Incident_ID = i.Incident_ID
   WHERE v.Priority_Level = 'Red'",

 "Q4. Vehicles that are free right now (subquery)" =>
  "SELECT Vehicle_ID, Vehicle_Type, Reg_Number FROM Vehicle
   WHERE Vehicle_ID NOT IN (SELECT Vehicle_ID FROM Dispatch WHERE Status IN ('Dispatched','En Route'))",

 "Q5. Drivers that are free right now" =>
  "SELECT Driver_ID, Name, Experience_Years FROM Driver
   WHERE Driver_ID NOT IN (SELECT Driver_ID FROM Dispatch WHERE Status IN ('Dispatched','En Route'))",

 "Q6. Beds left in each hospital" =>
  "SELECT f.Name, h.Total_Beds, COUNT(t.Victim_ID) AS Occupied, h.Total_Beds - COUNT(t.Victim_ID) AS Beds_Left
   FROM Hospital h JOIN Facility f ON h.Facility_ID = f.Facility_ID
   LEFT JOIN Transport t ON t.Facility_ID = h.Facility_ID
   GROUP BY f.Name, h.Total_Beds",

 "Q7. Places left in each shelter (stored function fn_beds_left)" =>
  "SELECT f.Name, s.Capacity, fn_beds_left(s.Facility_ID) AS Places_Left
   FROM Shelter s JOIN Facility f ON s.Facility_ID = f.Facility_ID",

 "Q8. Drivers with more experience than the average" =>
  "SELECT Name, Experience_Years FROM Driver
   WHERE Experience_Years > (SELECT AVG(Experience_Years) FROM Driver)",

 "Q9. Average trip time in minutes for each vehicle type" =>
  "SELECT v.Vehicle_Type, ROUND(AVG(TIMESTAMPDIFF(MINUTE, d.Dispatch_Time, d.Arrival_Time)), 1) AS Avg_Minutes
   FROM Dispatch d JOIN Vehicle v ON d.Vehicle_ID = v.Vehicle_ID
   WHERE d.Arrival_Time IS NOT NULL
   GROUP BY v.Vehicle_Type",

 "Q10. Full dispatch details (5 tables joined)" =>
  "SELECT d.Dispatch_ID, i.Incident_Type, v.Reg_Number, dr.Name AS Driver, r.Start_Location, r.End_Location, r.Distance_KM, d.Status
   FROM Dispatch d
   JOIN Incident i ON d.Incident_ID = i.Incident_ID
   JOIN Vehicle v ON d.Vehicle_ID = v.Vehicle_ID
   JOIN Driver dr ON d.Driver_ID = dr.Driver_ID
   JOIN Route r ON d.Route_ID = r.Route_ID",

 "Q11. Which victim went to which facility" =>
  "SELECT vi.Name AS Victim, vi.Priority_Level, f.Name AS Facility, f.Type, t.Pickup_Time, t.Dropoff_Time
   FROM Transport t
   JOIN Victim vi ON t.Victim_ID = vi.Victim_ID
   JOIN Facility f ON t.Facility_ID = f.Facility_ID
   ORDER BY t.Pickup_Time",

 "Q12. Number of dispatches done by every driver" =>
  "SELECT dr.Name, COUNT(d.Dispatch_ID) AS Total_Dispatches
   FROM Driver dr LEFT JOIN Dispatch d ON dr.Driver_ID = d.Driver_ID
   GROUP BY dr.Driver_ID, dr.Name",

 "Q13. All vehicles with subtype details (union)" =>
  "SELECT v.Vehicle_ID, v.Reg_Number, 'Ambulance' AS Subtype, a.Equip_Level AS Detail
   FROM Vehicle v JOIN Ambulance a ON v.Vehicle_ID = a.Vehicle_ID
   UNION
   SELECT v.Vehicle_ID, v.Reg_Number, 'Evacuation Bus', CONCAT(b.Seat_Capacity, ' seats')
   FROM Vehicle v JOIN Evacuation_Bus b ON v.Vehicle_ID = b.Vehicle_ID
   UNION
   SELECT v.Vehicle_ID, v.Reg_Number, 'Helicopter', CONCAT(h.Fuel_Capacity, ' L fuel')
   FROM Vehicle v JOIN Helicopter h ON v.Vehicle_ID = h.Vehicle_ID"
];

foreach ($queries as $title => $sql) {
    echo "<h3>" . htmlspecialchars($title) . "</h3>";
    echo "<pre style='background:#fff;border:1px solid #999;padding:8px;width:90%;white-space:pre-wrap'>" . htmlspecialchars($sql) . "</pre>";
    show_table($conn->query($sql));
}

// PL/SQL part: stored procedure with a cursor
echo "<h3>Q14. Facility capacity report (stored procedure sp_facility_report with cursor)</h3>";
echo "<pre style='background:#fff;border:1px solid #999;padding:8px;width:90%'>CALL sp_facility_report();</pre>";
$res = $conn->query("CALL sp_facility_report()");
show_table($res);
$res->free();
while ($conn->more_results()) {
    $conn->next_result();
}

include "footer.php";
?>
