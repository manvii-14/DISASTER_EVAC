// File 2: sample data (same rows as sql/1_schema_and_data.sql in DA-2)
// Run the whole file; statements are separated by semicolons.

// ---------- Reporters ----------
UNWIND [
 {id:1,name:'Arun Kumar',contact:'9840012345'},
 {id:2,name:'Priya Nair',contact:'9876501234'},
 {id:3,name:'Suresh Babu',contact:'9444098765'},
 {id:4,name:'Fatima Begum',contact:'9791123456'},
 {id:5,name:'Ravi Shankar',contact:'9585012345'}
] AS r
CREATE (:Reporter {id:r.id, name:r.name, contact:r.contact});

// ---------- Incidents (+ FILED) ----------
UNWIND [
 {id:1,type:'Building Collapse',sev:'Critical',loc:'Katpadi Road, Vellore',at:'2026-09-14T09:15:00',rep:1},
 {id:2,type:'Road Accident',sev:'High',loc:'Chennai-Bengaluru Highway, Ranipet',at:'2026-09-20T18:40:00',rep:2},
 {id:3,type:'Fire',sev:'High',loc:'Gandhi Nagar, Vellore',at:'2026-09-28T02:10:00',rep:3},
 {id:4,type:'Flood',sev:'Medium',loc:'Palar Riverbank, Walajapet',at:'2026-10-02T06:30:00',rep:4},
 {id:5,type:'Road Accident',sev:'Low',loc:'Bagayam Junction, Vellore',at:'2026-10-05T14:05:00',rep:2}
] AS i
MATCH (r:Reporter {id:i.rep})
CREATE (r)-[:FILED]->(:Incident {id:i.id, type:i.type, severity:i.sev, location:i.loc, reportedAt:datetime(i.at)});

// ---------- Victims (+ INVOLVES) ----------
UNWIND [
 {id:1,name:'Karthik S',age:34,p:'Red',inc:1},{id:2,name:'Lakshmi M',age:52,p:'Yellow',inc:1},
 {id:3,name:'Mohan R',age:27,p:'Red',inc:1},{id:4,name:'Divya P',age:8,p:'Yellow',inc:1},
 {id:5,name:'Anand V',age:41,p:'Red',inc:2},{id:6,name:'Selvi K',age:36,p:'Yellow',inc:2},
 {id:7,name:'Imran H',age:29,p:'Green',inc:2},{id:8,name:'Gopal T',age:61,p:'Red',inc:3},
 {id:9,name:'Meena A',age:45,p:'Yellow',inc:3},{id:10,name:'Rajesh D',age:19,p:'Green',inc:4},
 {id:11,name:'Sumathi L',age:67,p:'Yellow',inc:4},{id:12,name:'Bala N',age:23,p:'Green',inc:5}
] AS v
MATCH (i:Incident {id:v.inc})
CREATE (i)-[:INVOLVES]->(:Victim {id:v.id, name:v.name, age:v.age, priority:v.p});

// ---------- Vehicles: subtype = extra label (replaces ISA tables + UNION) ----------
UNWIND [{id:1,reg:'TN23AB1001',cap:4,equip:'ALS'},{id:2,reg:'TN23AB1002',cap:4,equip:'BLS'},
        {id:3,reg:'TN23AB1003',cap:4,equip:'ALS'}] AS v
CREATE (:Vehicle:Ambulance {id:v.id, reg:v.reg, capacity:v.cap, equip:v.equip});

UNWIND [{id:4,reg:'TN32EB2001',cap:40,seats:40},{id:5,reg:'TN32EB2002',cap:45,seats:45}] AS v
CREATE (:Vehicle:EvacuationBus {id:v.id, reg:v.reg, capacity:v.cap, seats:v.seats});

CREATE (:Vehicle:Helicopter {id:6, reg:'VTHLX01', capacity:6, fuel:500});

// ---------- Drivers ----------
UNWIND [
 {id:1,name:'Murugan P',lic:'TN0120150012345',exp:9},
 {id:2,name:'Senthil K',lic:'TN0220180023456',exp:6},
 {id:3,name:'Jayaraman S',lic:'TN0320120034567',exp:12},
 {id:4,name:'Abdul Rahman',lic:'TN0420190045678',exp:4},
 {id:5,name:'Vikram Singh',lic:'PLIN00987',exp:15}
] AS d
CREATE (:Driver {id:d.id, name:d.name, license:d.lic, experience:d.exp});

// ---------- Facilities: Hospital has totalBeds, Shelter has capacity ----------
UNWIND [{id:1,name:'CMC Hospital',beds:6},{id:2,name:'Vellore Govt Hospital',beds:4},
        {id:3,name:'Ranipet Govt Hospital',beds:3}] AS f
CREATE (:Facility:Hospital {id:f.id, name:f.name, totalBeds:f.beds});

UNWIND [{id:4,name:'Vellore Relief Camp',cap:50},{id:5,name:'Walajapet Relief Camp',cap:30}] AS f
CREATE (:Facility:Shelter {id:f.id, name:f.name, capacity:f.cap});

// ---------- Road network: the Route table becomes ROAD_TO edges ----------
UNWIND [
 {a:'Katpadi Road',b:'CMC Hospital',km:4.5},
 {a:'Katpadi Road',b:'Vellore Relief Camp',km:6.0},
 {a:'Ranipet Highway',b:'Ranipet Govt Hospital',km:8.2},
 {a:'Gandhi Nagar',b:'Vellore Govt Hospital',km:3.0},
 {a:'Palar Riverbank',b:'Walajapet Relief Camp',km:5.5},
 {a:'Bagayam Junction',b:'CMC Hospital',km:7.1}
] AS r
MERGE (x:Location {name:r.a})
MERGE (y:Location {name:r.b})
CREATE (x)-[:ROAD_TO {km:r.km, assumed:false}]->(y);

// Extra links so the map is connected (NOT in the DA-2 data: distances are assumed).
// Needed only for the "nearest hospital by road" demo. Mention this in the report.
UNWIND [
 {a:'Gandhi Nagar',b:'Katpadi Road',km:2.5},
 {a:'Bagayam Junction',b:'Gandhi Nagar',km:3.5},
 {a:'Ranipet Highway',b:'Katpadi Road',km:15.0},
 {a:'Palar Riverbank',b:'Ranipet Highway',km:12.0}
] AS r
MATCH (x:Location {name:r.a}), (y:Location {name:r.b})
CREATE (x)-[:ROAD_TO {km:r.km, assumed:true}]->(y);

// Link each facility to its entry point on the map
MATCH (f:Facility), (l:Location) WHERE f.name = l.name
CREATE (f)-[:LOCATED_AT]->(l);

// ---------- Dispatches (+ TRIGGERED, USES, DRIVEN_BY, FOLLOWS) ----------
UNWIND [
 {id:1,inc:1,veh:1,drv:1,from:'Katpadi Road',to:'CMC Hospital',t:'2026-09-14T09:25:00',a:'2026-09-14T09:40:00',s:'Completed'},
 {id:2,inc:1,veh:2,drv:2,from:'Katpadi Road',to:'Vellore Relief Camp',t:'2026-09-14T09:28:00',a:'2026-09-14T09:50:00',s:'Completed'},
 {id:3,inc:2,veh:3,drv:3,from:'Ranipet Highway',to:'Ranipet Govt Hospital',t:'2026-09-20T18:50:00',a:'2026-09-20T19:10:00',s:'Completed'},
 {id:4,inc:3,veh:1,drv:1,from:'Gandhi Nagar',to:'Vellore Govt Hospital',t:'2026-09-28T02:20:00',a:'2026-09-28T02:35:00',s:'Completed'},
 {id:5,inc:4,veh:5,drv:4,from:'Palar Riverbank',to:'Walajapet Relief Camp',t:'2026-10-02T06:45:00',a:null,s:'En Route'},
 {id:6,inc:5,veh:2,drv:2,from:'Bagayam Junction',to:'CMC Hospital',t:'2026-10-05T14:15:00',a:null,s:'Dispatched'}
] AS d
MATCH (i:Incident {id:d.inc}), (v:Vehicle {id:d.veh}), (dr:Driver {id:d.drv})
MATCH (:Location {name:d.from})-[rt:ROAD_TO]-(:Location {name:d.to})
CREATE (x:Dispatch {id:d.id, dispatchTime:datetime(d.t),
                    arrivalTime:CASE WHEN d.a IS NULL THEN null ELSE datetime(d.a) END,
                    status:d.s, routeFrom:d.from, routeTo:d.to, distanceKm:rt.km})
CREATE (i)-[:TRIGGERED]->(x)
CREATE (x)-[:USES]->(v)
CREATE (x)-[:DRIVEN_BY]->(dr);

// ---------- Transport (the ternary relationship) ----------
// (Dispatch)-[:CARRIED]->(Victim)   and   (Victim)-[:ADMITTED_TO {pickup, dropoff, dispatchId}]->(Facility)
UNWIND [
 {d:1,v:1,f:1,p:'2026-09-14T09:30:00',o:'2026-09-14T09:40:00'},
 {d:1,v:3,f:1,p:'2026-09-14T09:30:00',o:'2026-09-14T09:40:00'},
 {d:2,v:2,f:4,p:'2026-09-14T09:35:00',o:'2026-09-14T09:50:00'},
 {d:2,v:4,f:1,p:'2026-09-14T09:35:00',o:'2026-09-14T09:52:00'},
 {d:3,v:5,f:3,p:'2026-09-20T18:55:00',o:'2026-09-20T19:10:00'},
 {d:3,v:6,f:3,p:'2026-09-20T18:55:00',o:'2026-09-20T19:10:00'},
 {d:3,v:7,f:3,p:'2026-09-20T18:55:00',o:'2026-09-20T19:10:00'},
 {d:4,v:8,f:2,p:'2026-09-28T02:25:00',o:'2026-09-28T02:35:00'},
 {d:4,v:9,f:2,p:'2026-09-28T02:25:00',o:'2026-09-28T02:35:00'},
 {d:5,v:10,f:5,p:'2026-10-02T06:50:00',o:null},
 {d:5,v:11,f:5,p:'2026-10-02T06:50:00',o:null},
 {d:6,v:12,f:1,p:'2026-10-05T14:20:00',o:null}
] AS t
MATCH (d:Dispatch {id:t.d}), (v:Victim {id:t.v}), (f:Facility {id:t.f})
CREATE (d)-[:CARRIED]->(v)
CREATE (v)-[:ADMITTED_TO {dispatchId:t.d, pickup:datetime(t.p),
                          dropoff:CASE WHEN t.o IS NULL THEN null ELSE datetime(t.o) END}]->(f);
