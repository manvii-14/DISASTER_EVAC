// File 5: queries for the Aura Dashboards page (one query per dashboard card).
// Paste each query into its own card. Card type suggestions are in the comments.

// ---------- Row 1: single-value cards ----------

// Card 1 "Incidents"  (Single value)  expected: 5
MATCH (i:Incident) RETURN count(i) AS incidents;

// Card 2 "Victims"  (Single value)  expected: 12
MATCH (v:Victim) RETURN count(v) AS victims;

// Card 3 "Active dispatches"  (Single value)  expected: 2
MATCH (d:Dispatch) WHERE d.status IN ['Dispatched','En Route']
RETURN count(d) AS activeDispatches;

// Card 4 "Free vehicles"  (Single value)  expected: 4
MATCH (v:Vehicle)
WHERE NOT EXISTS { MATCH (d:Dispatch)-[:USES]->(v) WHERE d.status IN ['Dispatched','En Route'] }
RETURN count(v) AS freeVehicles;

// Card 5 "Hospital beds left"  (Single value)  expected: 4
MATCH (h:Hospital)
OPTIONAL MATCH (:Victim)-[a:ADMITTED_TO]->(h)
WITH h, count(a) AS occupied
RETURN sum(h.totalBeds - occupied) AS bedsLeft;

// ---------- Row 2: charts ----------

// Card 6 "Victims per incident"  (Pie chart)
MATCH (i:Incident)
OPTIONAL MATCH (i)-[:INVOLVES]->(v:Victim)
WITH i, count(v) AS victims
RETURN 'Incident ' + toString(i.id) + ' ' + i.type AS incident, victims
ORDER BY i.id;

// Card 7 "Victims by triage priority"  (Pie chart or Bar chart)  expected: Red 4, Yellow 5, Green 3
MATCH (v:Victim)
RETURN v.priority AS priority, count(v) AS victims
ORDER BY priority;

// Card 8 "Facility occupancy"  (Bar chart: category = facility, values = occupied, free)
MATCH (f:Facility)
OPTIONAL MATCH (:Victim)-[a:ADMITTED_TO]->(f)
WITH f, count(a) AS occupied
RETURN f.name AS facility, occupied, coalesce(f.totalBeds, f.capacity) - occupied AS free
ORDER BY facility;

// Card 9 "Dispatches per driver"  (Bar chart)
MATCH (dr:Driver)
OPTIONAL MATCH (d:Dispatch)-[:DRIVEN_BY]->(dr)
RETURN dr.name AS driver, count(d) AS dispatches
ORDER BY dispatches DESC;

// Card 10 "Average trip time by vehicle type (minutes)"  (Bar chart)
MATCH (d:Dispatch)-[:USES]->(v:Vehicle)
WHERE d.arrivalTime IS NOT NULL
RETURN [l IN labels(v) WHERE l <> 'Vehicle'][0] AS vehicleType,
       round(avg(duration.inSeconds(d.dispatchTime, d.arrivalTime).seconds / 60.0), 1) AS avgMinutes;

// ---------- Row 3: table and graph ----------

// Card 11 "Dispatch board"  (Table)
MATCH (i:Incident)-[:TRIGGERED]->(d:Dispatch)-[:USES]->(v:Vehicle), (d)-[:DRIVEN_BY]->(dr:Driver)
RETURN d.id AS dispatch, i.type AS incident, v.reg AS vehicle, dr.name AS driver,
       d.routeFrom AS fromPlace, d.routeTo AS toPlace, d.distanceKm AS km, d.status AS status
ORDER BY d.id;

// Card 12 "Road network and facilities"  (Graph)
MATCH (a:Location)-[r:ROAD_TO]-(b:Location) RETURN a, r, b;

// Card 13 "Whole system"  (Graph)
MATCH (n) OPTIONAL MATCH (n)-[r]->(m) RETURN n, r, m;
