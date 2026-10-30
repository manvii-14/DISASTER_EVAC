// =============== A. DA-3 queries ===============

// Q1: incidents with reporter (INNER JOIN -> one hop)
MATCH (r:Reporter)-[:FILED]->(i:Incident)
RETURN i.id, i.type, i.severity, i.location, r.name AS reportedBy, r.contact
ORDER BY i.id;

// Q2: victims per incident (LEFT JOIN + GROUP BY)
MATCH (i:Incident)
OPTIONAL MATCH (i)-[:INVOLVES]->(v:Victim)
RETURN i.id, i.type, count(v) AS totalVictims ORDER BY i.id;

// Q3: Red priority victims with incident
MATCH (i:Incident)-[:INVOLVES]->(v:Victim {priority:'Red'})
RETURN v.id, v.name, v.age, i.type, i.location;

// Q4: free vehicles (NOT IN subquery -> NOT EXISTS pattern)
MATCH (v:Vehicle)
WHERE NOT EXISTS { MATCH (d:Dispatch)-[:USES]->(v) WHERE d.status IN ['Dispatched','En Route'] }
RETURN v.id, [l IN labels(v) WHERE l <> 'Vehicle'][0] AS type, v.reg;

// Q5: free drivers
MATCH (dr:Driver)
WHERE NOT EXISTS { MATCH (d:Dispatch)-[:DRIVEN_BY]->(dr) WHERE d.status IN ['Dispatched','En Route'] }
RETURN dr.id, dr.name, dr.experience;

// Q6: beds left in each hospital
MATCH (h:Hospital)
OPTIONAL MATCH (:Victim)-[a:ADMITTED_TO]->(h)
RETURN h.name, h.totalBeds, count(a) AS occupied, h.totalBeds - count(a) AS bedsLeft;

// Q7: places left in each shelter (replaces fn_beds_left)
MATCH (s:Shelter)
OPTIONAL MATCH (:Victim)-[a:ADMITTED_TO]->(s)
RETURN s.name, s.capacity, s.capacity - count(a) AS placesLeft;

// Q8: drivers with above-average experience
MATCH (d:Driver) WITH avg(d.experience) AS avgExp
MATCH (x:Driver) WHERE x.experience > avgExp
RETURN x.name, x.experience;

// Q9: average trip minutes per vehicle type
MATCH (d:Dispatch)-[:USES]->(v:Vehicle)
WHERE d.arrivalTime IS NOT NULL
RETURN [l IN labels(v) WHERE l <> 'Vehicle'][0] AS vehicleType,
       round(avg(duration.inSeconds(d.dispatchTime, d.arrivalTime).seconds / 60.0), 1) AS avgMinutes;

// Q10: full dispatch details (5-table JOIN -> one pattern)
MATCH (i:Incident)-[:TRIGGERED]->(d:Dispatch)-[:USES]->(v:Vehicle),
      (d)-[:DRIVEN_BY]->(dr:Driver)
RETURN d.id, i.type, v.reg, dr.name AS driver, d.routeFrom, d.routeTo, d.distanceKm, d.status
ORDER BY d.id;

// Q11: which victim went to which facility
MATCH (v:Victim)-[a:ADMITTED_TO]->(f:Facility)
RETURN v.name, v.priority, f.name AS facility, a.pickup, a.dropoff
ORDER BY a.pickup;

// Q12: dispatches per driver, including zero
MATCH (dr:Driver)
OPTIONAL MATCH (d:Dispatch)-[:DRIVEN_BY]->(dr)
RETURN dr.name, count(d) AS totalDispatches;

// Q13: all vehicles with subtype detail (UNION of 3 joins -> labels + properties)
MATCH (v:Vehicle)
RETURN v.id, v.reg,
       [l IN labels(v) WHERE l <> 'Vehicle'][0] AS subtype,
       coalesce(v.equip, toString(v.seats) + ' seats', toString(v.fuel) + ' L fuel') AS detail;

// Facility report (replaces sp_facility_report cursor)
MATCH (f:Facility)
OPTIONAL MATCH (:Victim)-[a:ADMITTED_TO]->(f)
WITH f, coalesce(f.totalBeds, f.capacity) - count(a) AS left
RETURN f.name, left AS placesLeft,
       CASE WHEN left <= 0 THEN 'FULL' WHEN left <= 2 THEN 'ALMOST FULL' ELSE 'AVAILABLE' END AS status;

// =============== B. CRUD demo ===============

// Create a victim for incident 5
MATCH (i:Incident {id:5})
CREATE (i)-[:INVOLVES]->(v:Victim {id:13, name:'Test Victim', age:30, priority:'Green'})
RETURN v;

// Update priority Green -> Red
MATCH (v:Victim {id:13}) SET v.priority = 'Red' RETURN v;

// Delete (DETACH DELETE also removes relationships, like ON DELETE CASCADE)
MATCH (v:Victim {id:13}) DETACH DELETE v;

// =============== C. Business rules (replace the MySQL triggers) ===============
// Neo4j has no BEFORE INSERT triggers in the Community edition, so the rule is
// written into the query that does the write. If a rule fails, MATCH finds
// nothing and 0 rows are created.

// C1: assign victim 12 to dispatch 6, facility 1 - only if victim belongs to the
//     dispatch's incident AND the facility still has room.
MATCH (d:Dispatch {id:6})<-[:TRIGGERED]-(:Incident)-[:INVOLVES]->(v:Victim {id:12})
MATCH (f:Facility {id:3})
OPTIONAL MATCH (:Victim)-[a:ADMITTED_TO]->(f)
WITH d, v, f, count(a) AS used
WHERE coalesce(f.totalBeds, f.capacity) - used > 0
MERGE (d)-[:CARRIED]->(v)
MERGE (v)-[:ADMITTED_TO {dispatchId:d.id}]->(f)
RETURN v.name, f.name;
// returns 0 rows: facility full

// C2: block a double-booked vehicle: returns a row only if vehicle 2 is already busy
MATCH (d:Dispatch)-[:USES]->(v:Vehicle {id:2})
WHERE d.status IN ['Dispatched','En Route']
RETURN v.reg AS busyVehicle, d.id AS activeDispatch;

// C3: complete dispatch 6 (replaces sp_complete_dispatch)
MATCH (d:Dispatch {id:6})-[:CARRIED]->(v:Victim)-[a:ADMITTED_TO {dispatchId:6}]->(:Facility)
WHERE a.dropoff IS NULL
SET a.dropoff = datetime(), d.status = 'Completed', d.arrivalTime = datetime()
RETURN d.id, d.status, count(v) AS victimsDelivered;

// =============== D. Graph-specific queries (not practical in MySQL) ===============

// D1: nearest hospital BY ROAD that still has a free bed (weighted shortest path, no plugin)
//     From Ranipet Highway: Ranipet Govt is full, so the answer is CMC Hospital.
MATCH (start:Location {name:'Ranipet Highway'}), (h:Hospital)-[:LOCATED_AT]->(end:Location)
OPTIONAL MATCH (:Victim)-[a:ADMITTED_TO]->(h)
WITH start, end, h, h.totalBeds - count(a) AS bedsLeft
WHERE bedsLeft > 0
MATCH p = (start)-[:ROAD_TO*..6]-(end)
WITH h, bedsLeft, p, reduce(km = 0.0, r IN relationships(p) | km + r.km) AS totalKm
RETURN h.name AS hospital, bedsLeft, round(totalKm * 10) / 10 AS km,
       [n IN nodes(p) | n.name] AS route
ORDER BY totalKm LIMIT 1;

// D2: whole story of one victim: reporter -> incident -> dispatch -> vehicle/driver -> facility
MATCH path = (r:Reporter)-[:FILED]->(:Incident)-[:INVOLVES]->(v:Victim {name:'Karthik S'}),
      (v)<-[:CARRIED]-(d:Dispatch)-[:USES]->(veh:Vehicle),
      (d)-[:DRIVEN_BY]->(dr:Driver), (v)-[:ADMITTED_TO]->(f:Facility)
RETURN path, d, veh, dr, f;

// D3: who is affected if driver Murugan P is unavailable? (impact analysis)
MATCH (dr:Driver {name:'Murugan P'})<-[:DRIVEN_BY]-(d:Dispatch)-[:CARRIED]->(v:Victim)
RETURN d.id AS dispatch, collect(v.name) AS victimsAffected;

// D4: view the full graph for the screenshot
MATCH (n) OPTIONAL MATCH (n)-[r]->(m) RETURN n, r, m;
