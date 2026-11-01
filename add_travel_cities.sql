-- Run once against an existing JB Travels database to add the latest
-- supported city list. The statements are safe to run again.

UPDATE locations
SET city = 'Bengaluru'
WHERE city = 'Bangalore'
  AND NOT EXISTS (
      SELECT 1
      FROM (SELECT city FROM locations) AS current_cities
      WHERE current_cities.city = 'Bengaluru'
  );

UPDATE locations
SET city = 'Mysuru'
WHERE city = 'Mysore'
  AND NOT EXISTS (
      SELECT 1
      FROM (SELECT city FROM locations) AS current_cities
      WHERE current_cities.city = 'Mysuru'
  );

UPDATE locations
SET city = 'Mangaluru'
WHERE city = 'Mangalore'
  AND NOT EXISTS (
      SELECT 1
      FROM (SELECT city FROM locations) AS current_cities
      WHERE current_cities.city = 'Mangaluru'
  );

INSERT INTO locations (city, state, latitude, longitude)
SELECT new_city.city, new_city.state, new_city.latitude, new_city.longitude
FROM (
    SELECT 'Belagavi' AS city, 'Karnataka' AS state, 15.8497 AS latitude, 74.4977 AS longitude
    UNION ALL SELECT 'Hubballi', 'Karnataka', 15.3647, 75.1240
    UNION ALL SELECT 'Pune', 'Maharashtra', 18.5204, 73.8567
    UNION ALL SELECT 'Goa', 'Goa', 15.2993, 74.1240
    UNION ALL SELECT 'Bengaluru', 'Karnataka', 12.9716, 77.5946
    UNION ALL SELECT 'Mumbai', 'Maharashtra', 19.0760, 72.8777
    UNION ALL SELECT 'Delhi', 'Delhi', 28.6139, 77.2090
    UNION ALL SELECT 'Jaipur', 'Rajasthan', 26.9124, 75.7873
    UNION ALL SELECT 'Kolkata', 'West Bengal', 22.5726, 88.3639
    UNION ALL SELECT 'Chennai', 'Tamil Nadu', 13.0827, 80.2707
    UNION ALL SELECT 'Hyderabad', 'Telangana', 17.3850, 78.4867
    UNION ALL SELECT 'Visakhapatnam', 'Andhra Pradesh', 17.6868, 83.2185
    UNION ALL SELECT 'Bengaluru International Airport', 'Karnataka', 13.1986, 77.7066
    UNION ALL SELECT 'Chandigarh', 'Chandigarh', 30.7333, 76.7794
    UNION ALL SELECT 'Ahmedabad', 'Gujarat', 23.0225, 72.5714
    UNION ALL SELECT 'Surat', 'Gujarat', 21.1702, 72.8311
    UNION ALL SELECT 'Lucknow', 'Uttar Pradesh', 26.8467, 80.9462
    UNION ALL SELECT 'Indore', 'Madhya Pradesh', 22.7196, 75.8577
    UNION ALL SELECT 'Nagpur', 'Maharashtra', 21.1458, 79.0882
    UNION ALL SELECT 'Coimbatore', 'Tamil Nadu', 11.0168, 76.9558
    UNION ALL SELECT 'Thiruvananthapuram', 'Kerala', 8.5241, 76.9366
    UNION ALL SELECT 'Gandhinagar', 'Gujarat', 23.2156, 72.6369
    UNION ALL SELECT 'Rajkot', 'Gujarat', 22.3039, 70.8022
    UNION ALL SELECT 'Madurai', 'Tamil Nadu', 9.9252, 78.1198
    UNION ALL SELECT 'Kochi', 'Kerala', 9.9312, 76.2673
    UNION ALL SELECT 'Mysuru', 'Karnataka', 12.2958, 76.6394
    UNION ALL SELECT 'Shivamogga', 'Karnataka', 13.9299, 75.5681
    UNION ALL SELECT 'Kolar', 'Karnataka', 13.1367, 78.1292
    UNION ALL SELECT 'Mangaluru', 'Karnataka', 12.9141, 74.8560
    UNION ALL SELECT 'Raichur', 'Karnataka', 16.2120, 77.3439
    UNION ALL SELECT 'Bidar', 'Karnataka', 17.9133, 77.5301
    UNION ALL SELECT 'Kalaburagi', 'Karnataka', 17.3297, 76.8343
    UNION ALL SELECT 'Vijayanagara', 'Karnataka', 15.2695, 76.3871
    UNION ALL SELECT 'Hassan', 'Karnataka', 13.0068, 76.0996
    UNION ALL SELECT 'Davangere', 'Karnataka', 14.4644, 75.9218
    UNION ALL SELECT 'Tumakuru', 'Karnataka', 13.3379, 77.1173
    UNION ALL SELECT 'Chitradurga', 'Karnataka', 14.2251, 76.3980
    UNION ALL SELECT 'Bagalkot', 'Karnataka', 16.1691, 75.6615
    UNION ALL SELECT 'Gadag', 'Karnataka', 15.4167, 75.6167
    UNION ALL SELECT 'Dharwad', 'Karnataka', 15.4589, 75.0078
    UNION ALL SELECT 'Uttara Kannada', 'Karnataka', 14.8136, 74.1297
) AS new_city
WHERE NOT EXISTS (
    SELECT 1
    FROM locations AS existing_city
    WHERE LOWER(existing_city.city) = LOWER(new_city.city)
);
