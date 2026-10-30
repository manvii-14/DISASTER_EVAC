// File 4: Insert / Update / Delete scope.
// Uses NEW ids (reporter 6, incident 6, victims 13-14, vehicle 7, driver 6,
// facility 6, dispatch 7) so the original DA-2 data is never touched.
// Run one statement at a time, in order. The last section removes everything it added.

// =====================================================================
// PART 1 - INSERTION
// =====================================================================

// 1.1 New reporter + new incident in one statement
MERGE (r:Reporter {id:6}) ON CREATE SET r.name = 'Kavitha R', r.contact = '9000000001'
CREATE (r)-[:FILED]->(i:Incident {id:6, type:'Fire', severity:'Medium',
        location:'Sathuvachari, Vellore', reportedAt:datetime('2026-10-30T10:00:00')})
RETURN r, i;

// 1.2 Two victims of that incident
UNWIND [{id:13,name:'Test Victim A',age:31,p:'Red'},{id:14,name:'Test Victim B',age:58,p:'Green'}] AS v
MATCH (i:Incident {id:6})
CREATE (i)-[:INVOLVES]->(:Victim {id:v.id, name:v.name, age:v.age, priority:v.p});

// 1.3 New resources: ambulance, driver
CREATE (:Vehicle:Ambulance {id:7, reg:'TN23AB1004', capacity:4, equip:'BLS'});
CREATE (:Driver {id:6, name:'Test Driver', license:'TN0520210056789', experience:3});

// 1.4 New hospital + its map location + a road to the existing network
MATCH (g:Location {name:'Gandhi Nagar'})
CREATE (h:Facility:Hospital {id:6, name:'Sathuvachari Clinic', totalBeds:2})
MERGE (l:Location {name:'Sathuvachari Clinic'})
CREATE (h)-[:LOCATED_AT]->(l)
CREATE (g)-[:ROAD_TO {km:4.0, assumed:true}]->(l)
RETURN h, l, g;

// 1.5 Create a dispatch ONLY IF vehicle 7 and driver 6 are free (rule in the query)
MATCH (i:Incident {id:6}), (v:Vehicle {id:7}), (dr:Driver {id:6})
WHERE NOT EXISTS { MATCH (d:Dispatch)-[:USES]->(v) WHERE d.status IN ['Dispatched','En Route'] }
  AND NOT EXISTS { MATCH (d:Dispatch)-[:DRIVEN_BY]->(dr) WHERE d.status IN ['Dispatched','En Route'] }
CREATE (x:Dispatch {id:7, dispatchTime:datetime(), status:'Dispatched',
                    routeFrom:'Gandhi Nagar', routeTo:'Sathuvachari Clinic', distanceKm:4.0})
CREATE (i)-[:TRIGGERED]->(x)
CREATE (x)-[:USES]->(v)
CREATE (x)-[:DRIVEN_BY]->(dr)
RETURN x;
// Run 1.5 a second time: 0 rows, because the vehicle is now busy.

// 1.6 Admit victim 13 to facility 6 ONLY IF a bed is free (one victim per statement,
//     so the bed count is always accurate)
MATCH (d:Dispatch {id:7})<-[:TRIGGERED]-(:Incident)-[:INVOLVES]->(v:Victim {id:13})
MATCH (f:Facility {id:6})
OPTIONAL MATCH (:Victim)-[a:ADMITTED_TO]->(f)
WITH d, v, f, count(a) AS used
WHERE coalesce(f.totalBeds, f.capacity) - used > 0
CREATE (d)-[:CARRIED]->(v)
CREATE (v)-[:ADMITTED_TO {dispatchId:d.id, pickup:datetime()}]->(f)
RETURN v.name, f.name;

// =====================================================================
// PART 2 - UPDATION
// =====================================================================

// 2.1 Change a property: re-triage victim 13 and raise incident severity
MATCH (v:Victim {id:13}) SET v.priority = 'Yellow' RETURN v.name, v.priority;
MATCH (i:Incident {id:6}) SET i.severity = 'High' RETURN i.id, i.severity;

// 2.2 Bulk update: set several test victims at once
MATCH (v:Victim) WHERE v.id IN [13, 14] SET v.priority = 'Yellow' RETURN v.id, v.priority;

// 2.3 Update a hospital's capacity
MATCH (h:Hospital {id:6}) SET h.totalBeds = 3 RETURN h.name, h.totalBeds;

// 2.4 Update a road distance (affects the nearest-hospital query)
MATCH (:Location {name:'Gandhi Nagar'})-[r:ROAD_TO]-(:Location {name:'Sathuvachari Clinic'})
SET r.km = 4.5 RETURN r.km;

// 2.5 Move a dispatch through its life cycle
MATCH (d:Dispatch {id:7}) SET d.status = 'En Route' RETURN d.id, d.status;

// 2.6 Reassign the driver (change a relationship): only to a free driver
MATCH (d:Dispatch {id:7})-[old:DRIVEN_BY]->(:Driver)
MATCH (nd:Driver {id:1})
WHERE NOT EXISTS { MATCH (x:Dispatch)-[:DRIVEN_BY]->(nd) WHERE x.status IN ['Dispatched','En Route'] }
DELETE old
CREATE (d)-[:DRIVEN_BY]->(nd)
RETURN d.id, nd.name;

// 2.7 Move victim 13 to a different facility (CMC Hospital), only if it has room
MATCH (v:Victim {id:13})-[old:ADMITTED_TO]->(:Facility)
MATCH (f:Facility {id:1})
OPTIONAL MATCH (:Victim)-[a:ADMITTED_TO]->(f)
WITH v, old, f, count(a) AS used
WHERE coalesce(f.totalBeds, f.capacity) - used > 0
CREATE (v)-[:ADMITTED_TO {dispatchId:old.dispatchId, pickup:old.pickup, dropoff:old.dropoff}]->(f)
DELETE old
RETURN v.name, f.name;

// 2.8 Complete the dispatch and record the drop-off time
MATCH (d:Dispatch {id:7})-[:CARRIED]->(v:Victim)-[a:ADMITTED_TO {dispatchId:7}]->(:Facility)
SET a.dropoff = datetime(), d.status = 'Completed', d.arrivalTime = datetime()
RETURN d.id, d.status, count(v) AS delivered;

// =====================================================================
// PART 3 - DELETION
// =====================================================================

// 3.1 Soft delete (keeps history): cancel a dispatch instead of removing it
MATCH (d:Dispatch {id:7}) SET d.status = 'Cancelled' RETURN d.id, d.status;

// 3.2 Delete one relationship: remove a road
MATCH (:Location {name:'Gandhi Nagar'})-[r:ROAD_TO]-(:Location {name:'Sathuvachari Clinic'})
DELETE r;

// 3.3 Safe delete of a facility: allowed only if no victim is admitted there
//     (facility 6 is empty after 2.7, so it is deleted together with its map point)
MATCH (f:Facility {id:6})
WHERE NOT EXISTS { MATCH (:Victim)-[:ADMITTED_TO]->(f) }
OPTIONAL MATCH (f)-[:LOCATED_AT]->(l:Location)
DETACH DELETE f, l;

// 3.4 Blocked delete: dispatch 7 still carries a victim, so this deletes nothing
MATCH (d:Dispatch {id:7})
WHERE NOT EXISTS { MATCH (d)-[:CARRIED]->(:Victim) }
DETACH DELETE d
RETURN count(*) AS deleted;

// 3.5 Cascade delete: remove incident 6 with its victims and dispatches
MATCH (i:Incident {id:6})
OPTIONAL MATCH (i)-[:INVOLVES]->(v:Victim)
OPTIONAL MATCH (i)-[:TRIGGERED]->(d:Dispatch)
DETACH DELETE v, d, i;

// 3.6 Remove leftovers: reporter, vehicle, driver (each only if unused)
MATCH (r:Reporter {id:6}) WHERE NOT EXISTS { (r)-[:FILED]->() } DETACH DELETE r;
MATCH (v:Vehicle {id:7})  WHERE NOT EXISTS { (:Dispatch)-[:USES]->(v) } DETACH DELETE v;
MATCH (d:Driver {id:6})   WHERE NOT EXISTS { (:Dispatch)-[:DRIVEN_BY]->(d) } DETACH DELETE d;

// 3.7 Check: counts should be back to the original data
MATCH (n) RETURN labels(n)[0] AS label, count(*) AS total ORDER BY label;
