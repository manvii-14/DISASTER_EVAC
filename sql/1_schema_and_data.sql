-- Evacuation & Emergency Transport Management System
-- File 1: create database, tables and sample data

DROP DATABASE IF EXISTS evac_db;
CREATE DATABASE evac_db;
USE evac_db;

CREATE TABLE Reporter (
    Reporter_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(50) NOT NULL,
    Contact_Number VARCHAR(15) NOT NULL
);

CREATE TABLE Incident (
    Incident_ID INT AUTO_INCREMENT PRIMARY KEY,
    Incident_Type VARCHAR(30) NOT NULL,
    Severity_Level ENUM('Low','Medium','High','Critical') NOT NULL,
    Location VARCHAR(100) NOT NULL,
    Date_Reported DATE NOT NULL,
    Time_Reported TIME NOT NULL,
    Reporter_ID INT NOT NULL,
    FOREIGN KEY (Reporter_ID) REFERENCES Reporter(Reporter_ID)
);

CREATE TABLE Victim (
    Victim_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(50) NOT NULL,
    Age INT NOT NULL CHECK (Age >= 0 AND Age <= 120),
    Priority_Level ENUM('Red','Yellow','Green') NOT NULL,
    Incident_ID INT NOT NULL,
    FOREIGN KEY (Incident_ID) REFERENCES Incident(Incident_ID)
);

CREATE TABLE Vehicle (
    Vehicle_ID INT AUTO_INCREMENT PRIMARY KEY,
    Vehicle_Type ENUM('Ambulance','Evacuation Bus','Helicopter') NOT NULL,
    Reg_Number VARCHAR(20) NOT NULL UNIQUE,
    Capacity INT NOT NULL CHECK (Capacity > 0)
);

CREATE TABLE Ambulance (
    Vehicle_ID INT PRIMARY KEY,
    Equip_Level VARCHAR(20) NOT NULL,
    FOREIGN KEY (Vehicle_ID) REFERENCES Vehicle(Vehicle_ID) ON DELETE CASCADE
);

CREATE TABLE Evacuation_Bus (
    Vehicle_ID INT PRIMARY KEY,
    Seat_Capacity INT NOT NULL,
    FOREIGN KEY (Vehicle_ID) REFERENCES Vehicle(Vehicle_ID) ON DELETE CASCADE
);

CREATE TABLE Helicopter (
    Vehicle_ID INT PRIMARY KEY,
    Fuel_Capacity INT NOT NULL,
    FOREIGN KEY (Vehicle_ID) REFERENCES Vehicle(Vehicle_ID) ON DELETE CASCADE
);

CREATE TABLE Driver (
    Driver_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(50) NOT NULL,
    License_No VARCHAR(25) NOT NULL UNIQUE,
    Experience_Years INT NOT NULL CHECK (Experience_Years >= 0)
);

CREATE TABLE Route (
    Route_ID INT AUTO_INCREMENT PRIMARY KEY,
    Start_Location VARCHAR(60) NOT NULL,
    End_Location VARCHAR(60) NOT NULL,
    Distance_KM DECIMAL(6,2) NOT NULL,
    UNIQUE (Start_Location, End_Location)
);

CREATE TABLE Dispatch (
    Dispatch_ID INT AUTO_INCREMENT PRIMARY KEY,
    Incident_ID INT NOT NULL,
    Vehicle_ID INT NOT NULL,
    Driver_ID INT NOT NULL,
    Route_ID INT NOT NULL,
    Dispatch_Time DATETIME NOT NULL,
    Arrival_Time DATETIME NULL,
    Status ENUM('Dispatched','En Route','Completed','Cancelled') NOT NULL DEFAULT 'Dispatched',
    FOREIGN KEY (Incident_ID) REFERENCES Incident(Incident_ID),
    FOREIGN KEY (Vehicle_ID) REFERENCES Vehicle(Vehicle_ID),
    FOREIGN KEY (Driver_ID) REFERENCES Driver(Driver_ID),
    FOREIGN KEY (Route_ID) REFERENCES Route(Route_ID)
);

CREATE TABLE Facility (
    Facility_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(80) NOT NULL,
    Type ENUM('Hospital','Shelter') NOT NULL
);

CREATE TABLE Hospital (
    Facility_ID INT PRIMARY KEY,
    Total_Beds INT NOT NULL CHECK (Total_Beds >= 0),
    FOREIGN KEY (Facility_ID) REFERENCES Facility(Facility_ID) ON DELETE CASCADE
);

CREATE TABLE Shelter (
    Facility_ID INT PRIMARY KEY,
    Capacity INT NOT NULL CHECK (Capacity >= 0),
    FOREIGN KEY (Facility_ID) REFERENCES Facility(Facility_ID) ON DELETE CASCADE
);

CREATE TABLE Transport (
    Dispatch_ID INT NOT NULL,
    Victim_ID INT NOT NULL,
    Facility_ID INT NOT NULL,
    Pickup_Time DATETIME NOT NULL,
    Dropoff_Time DATETIME NULL,
    PRIMARY KEY (Dispatch_ID, Victim_ID),
    FOREIGN KEY (Dispatch_ID) REFERENCES Dispatch(Dispatch_ID) ON DELETE CASCADE,
    FOREIGN KEY (Victim_ID) REFERENCES Victim(Victim_ID) ON DELETE CASCADE,
    FOREIGN KEY (Facility_ID) REFERENCES Facility(Facility_ID)
);

-- extra table only for the login page (not part of the DA-1 design)
CREATE TABLE Users (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    Full_Name VARCHAR(50) NOT NULL,
    Username VARCHAR(30) NOT NULL UNIQUE,
    Password_Hash VARCHAR(255) NOT NULL
);

-- ---------- sample data ----------

INSERT INTO Reporter (Name, Contact_Number) VALUES
('Arun Kumar', '9840012345'),
('Priya Nair', '9876501234'),
('Suresh Babu', '9444098765'),
('Fatima Begum', '9791123456'),
('Ravi Shankar', '9585012345');

INSERT INTO Incident (Incident_Type, Severity_Level, Location, Date_Reported, Time_Reported, Reporter_ID) VALUES
('Building Collapse', 'Critical', 'Katpadi Road, Vellore', '2026-09-14', '09:15:00', 1),
('Road Accident', 'High', 'Chennai-Bengaluru Highway, Ranipet', '2026-09-20', '18:40:00', 2),
('Fire', 'High', 'Gandhi Nagar, Vellore', '2026-09-28', '02:10:00', 3),
('Flood', 'Medium', 'Palar Riverbank, Walajapet', '2026-10-02', '06:30:00', 4),
('Road Accident', 'Low', 'Bagayam Junction, Vellore', '2026-10-05', '14:05:00', 2);

INSERT INTO Victim (Name, Age, Priority_Level, Incident_ID) VALUES
('Karthik S', 34, 'Red', 1),
('Lakshmi M', 52, 'Yellow', 1),
('Mohan R', 27, 'Red', 1),
('Divya P', 8, 'Yellow', 1),
('Anand V', 41, 'Red', 2),
('Selvi K', 36, 'Yellow', 2),
('Imran H', 29, 'Green', 2),
('Gopal T', 61, 'Red', 3),
('Meena A', 45, 'Yellow', 3),
('Rajesh D', 19, 'Green', 4),
('Sumathi L', 67, 'Yellow', 4),
('Bala N', 23, 'Green', 5);

INSERT INTO Vehicle (Vehicle_Type, Reg_Number, Capacity) VALUES
('Ambulance', 'TN23AB1001', 4),
('Ambulance', 'TN23AB1002', 4),
('Ambulance', 'TN23AB1003', 4),
('Evacuation Bus', 'TN32EB2001', 40),
('Evacuation Bus', 'TN32EB2002', 45),
('Helicopter', 'VTHLX01', 6);

INSERT INTO Ambulance VALUES (1, 'ALS'), (2, 'BLS'), (3, 'ALS');
INSERT INTO Evacuation_Bus VALUES (4, 40), (5, 45);
INSERT INTO Helicopter VALUES (6, 500);

INSERT INTO Driver (Name, License_No, Experience_Years) VALUES
('Murugan P', 'TN0120150012345', 9),
('Senthil K', 'TN0220180023456', 6),
('Jayaraman S', 'TN0320120034567', 12),
('Abdul Rahman', 'TN0420190045678', 4),
('Vikram Singh', 'PLIN00987', 15);

INSERT INTO Route (Start_Location, End_Location, Distance_KM) VALUES
('Katpadi Road', 'CMC Hospital', 4.50),
('Katpadi Road', 'Vellore Relief Camp', 6.00),
('Ranipet Highway', 'Ranipet Govt Hospital', 8.20),
('Gandhi Nagar', 'Vellore Govt Hospital', 3.00),
('Palar Riverbank', 'Walajapet Relief Camp', 5.50),
('Bagayam Junction', 'CMC Hospital', 7.10);

INSERT INTO Dispatch (Incident_ID, Vehicle_ID, Driver_ID, Route_ID, Dispatch_Time, Arrival_Time, Status) VALUES
(1, 1, 1, 1, '2026-09-14 09:25:00', '2026-09-14 09:40:00', 'Completed'),
(1, 2, 2, 2, '2026-09-14 09:28:00', '2026-09-14 09:50:00', 'Completed'),
(2, 3, 3, 3, '2026-09-20 18:50:00', '2026-09-20 19:10:00', 'Completed'),
(3, 1, 1, 4, '2026-09-28 02:20:00', '2026-09-28 02:35:00', 'Completed'),
(4, 5, 4, 5, '2026-10-02 06:45:00', NULL, 'En Route'),
(5, 2, 2, 6, '2026-10-05 14:15:00', NULL, 'Dispatched');

INSERT INTO Facility (Name, Type) VALUES
('CMC Hospital', 'Hospital'),
('Vellore Govt Hospital', 'Hospital'),
('Ranipet Govt Hospital', 'Hospital'),
('Vellore Relief Camp', 'Shelter'),
('Walajapet Relief Camp', 'Shelter');

INSERT INTO Hospital VALUES (1, 6), (2, 4), (3, 3);
INSERT INTO Shelter VALUES (4, 50), (5, 30);

INSERT INTO Transport (Dispatch_ID, Victim_ID, Facility_ID, Pickup_Time, Dropoff_Time) VALUES
(1, 1, 1, '2026-09-14 09:30:00', '2026-09-14 09:40:00'),
(1, 3, 1, '2026-09-14 09:30:00', '2026-09-14 09:40:00'),
(2, 2, 4, '2026-09-14 09:35:00', '2026-09-14 09:50:00'),
(2, 4, 1, '2026-09-14 09:35:00', '2026-09-14 09:52:00'),
(3, 5, 3, '2026-09-20 18:55:00', '2026-09-20 19:10:00'),
(3, 6, 3, '2026-09-20 18:55:00', '2026-09-20 19:10:00'),
(3, 7, 3, '2026-09-20 18:55:00', '2026-09-20 19:10:00'),
(4, 8, 2, '2026-09-28 02:25:00', '2026-09-28 02:35:00'),
(4, 9, 2, '2026-09-28 02:25:00', '2026-09-28 02:35:00'),
(5, 10, 5, '2026-10-02 06:50:00', NULL),
(5, 11, 5, '2026-10-02 06:50:00', NULL),
(6, 12, 1, '2026-10-05 14:20:00', NULL);
