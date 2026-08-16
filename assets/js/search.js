document.addEventListener('DOMContentLoaded', function() {
  var searchBtn = document.getElementById('searchCars');
  var resultsEl = document.getElementById('searchResults');
  if (!searchBtn) return;

  searchBtn.addEventListener('click', function() {
    var tripType = document.getElementById('tripType').value || 'local';
    var pickupInput = document.getElementById('pickup').value.trim();
    var dropInput = document.getElementById('drop').value.trim();
    var pickupDate = document.getElementById('date').value;
    var pickupTime = document.getElementById('time').value;

    // Basic client-side validation
    if (!pickupInput || !dropInput) {
      resultsEl.innerHTML = '<div class="err">Please enter pickup and drop locations.</div>';
      return;
    }

    // For now locations API not wired to form IDs; developers can wire ids after selecting from autocomplete
    // Send a simple payload using placeholder location IDs (1 and 2) when real IDs are not available.
    var payload = {
      service_type: tripType.toUpperCase(),
      trip_type: 'ONE_WAY',
      from_location_id: 1,
      to_location_id: 2,
      pickup_date: pickupDate || new Date().toISOString().slice(0,10),
      pickup_time: pickupTime || '09:00'
    };

    resultsEl.innerHTML = '<div class="loading">Searching cars…</div>';

    fetch('api/search.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function(res) { return res.json(); })
      .then(function(json) {
        if (!json.success) {
          resultsEl.innerHTML = '<div class="err">'+(json.message || 'No cars found')+'</div>';
          return;
        }

        var html = '';
        if ((json.cars || []).length === 0) {
          html = '<div class="note">No matching vehicles available for the selected route.</div>';
        } else {
          html += '<div class="results-grid">';
          json.cars.forEach(function(car) {
            var fare = car.fare_breakdown || {};
            html += '<div class="car-card">';
            html += '<div class="car-img">'+(car.image?'<img src="'+car.image+'">':'<div class="placeholder">Car</div>')+'</div>';
            html += '<div class="car-body">';
            html += '<h4>'+escapeHtml(car.car_name)+' '+escapeHtml(car.variant_name||'')+'</h4>';
            html += '<div class="meta">'+escapeHtml(car.category)+' · '+car.seats+' seats · '+escapeHtml(car.ac_type)+'</div>';
            html += '<div class="price">₹'+(fare.total || '0')+'<small> total</small></div>';
            html += '<div class="actions"><button class="btn btn-ghost" onclick="alert(\'Fare details will open in the next step.\')">FARE DETAILS</button> <button class="btn btn-yellow" onclick="alert(\'Proceed to booking flow.\')">BOOK NOW</button></div>';
            html += '</div></div>';
          });
          html += '</div>';
        }

        resultsEl.innerHTML = html;
      }).catch(function(err){
        resultsEl.innerHTML = '<div class="err">Could not reach server.</div>';
      });
  });

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; });
  }
});
