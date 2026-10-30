// DA-3 : Evacuation & Emergency Transport - Neo4j
// File 1: constraints and indexes  (run once, on an empty database)

CREATE CONSTRAINT reporter_id  IF NOT EXISTS FOR (n:Reporter)  REQUIRE n.id IS UNIQUE;
CREATE CONSTRAINT incident_id  IF NOT EXISTS FOR (n:Incident)  REQUIRE n.id IS UNIQUE;
CREATE CONSTRAINT victim_id    IF NOT EXISTS FOR (n:Victim)    REQUIRE n.id IS UNIQUE;
CREATE CONSTRAINT vehicle_id   IF NOT EXISTS FOR (n:Vehicle)   REQUIRE n.id IS UNIQUE;
CREATE CONSTRAINT vehicle_reg  IF NOT EXISTS FOR (n:Vehicle)   REQUIRE n.reg IS UNIQUE;
CREATE CONSTRAINT driver_id    IF NOT EXISTS FOR (n:Driver)    REQUIRE n.id IS UNIQUE;
CREATE CONSTRAINT driver_lic   IF NOT EXISTS FOR (n:Driver)    REQUIRE n.license IS UNIQUE;
CREATE CONSTRAINT dispatch_id  IF NOT EXISTS FOR (n:Dispatch)  REQUIRE n.id IS UNIQUE;
CREATE CONSTRAINT facility_id  IF NOT EXISTS FOR (n:Facility)  REQUIRE n.id IS UNIQUE;
CREATE CONSTRAINT location_nm  IF NOT EXISTS FOR (n:Location)  REQUIRE n.name IS UNIQUE;

CREATE INDEX victim_priority IF NOT EXISTS FOR (n:Victim)   ON (n.priority);
CREATE INDEX dispatch_status IF NOT EXISTS FOR (n:Dispatch) ON (n.status);
