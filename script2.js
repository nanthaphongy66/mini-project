// ==========================
// สร้างแผนที่
const map = L.map("map").setView([16.5, 100.5], 8);

// BaseMap
const osm = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
  maxZoom: 18,
  attribution: "&copy; OpenStreetMap contributors",
}).addTo(map);
const satellite = L.tileLayer(
  "https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}",
  {
    maxZoom: 20,
    subdomains: ["mt0", "mt1", "mt2", "mt3"],
    attribution: "Google Satellite",
  }
);

const topo = L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", {
  maxZoom: 17,
  attribution:
    "Map data: &copy; OpenStreetMap, SRTM | Map style: &copy; OpenTopoMap",
});

// ==========================
// GeoJSON
function popUp(f, l) {
  var out = [];
  if (f.properties) {
    for (key in f.properties) {
      out.push(key + ": " + f.properties[key]);
    }
    l.bindPopup(out.join("<br />"));
  }
}
var ThaiProvJSON = new L.GeoJSON.AJAX(["./thailand_province.geojson"], {
  onEachFeature: popUp,
});
// ==========================
// LayerGroup สำหรับฝน
const rainLayer = L.layerGroup();

async function loadRainData() {
  try {
    const url =
      "https://api-v3.thaiwater.net/api/v1/thaiwater30/public/thailand_main_rain?province_code=65";
    const response = await axios.get(url);
    const data = response.data.data;

    // icon เดียวสำหรับฝน
    const rainIcon = L.icon({
      iconUrl: "https://cdn-icons-png.flaticon.com/512/414/414974.png",
      iconSize: [32, 32],
      iconAnchor: [16, 32],
      popupAnchor: [0, -32],
    });

    data.forEach((item) => {
      const lat = item.station.tele_station_lat;
      const lon = item.station.tele_station_long;
      const name = item.station.tele_station_name.th;
      const rain = item.rain_24h ?? 0;
      const time = item.rainfall_datetime;
      const province = item.geocode.province_name.th;

      const marker = L.marker([lat, lon], { icon: rainIcon }).bindPopup(`
        <b>${name}</b><br>
        🌧️ ปริมาณฝน 24 ชม.: <b>${rain} มม.</b><br>
        🕒 เวลา: ${time}<br>
        📍 จังหวัด: ${province}
      `);

      marker.addTo(rainLayer);
    });
  } catch (error) {
    console.error(error);
  }
}
loadRainData();

// ==========================
// LayerGroup สำหรับระดับน้ำ
const waterLayer = L.layerGroup();

async function loadWaterData() {
  try {
    const url =
      "https://api-v3.thaiwater.net/api/v1/thaiwater30/public/waterlevel_load";
    const response = await axios.get(url);
    const data = response.data.waterlevel_data.data;

    // icon เดียวสำหรับน้ำ
    const waterIcon = L.icon({
      iconUrl: "./assets/img/water-level.png",
      iconSize: [32, 32],
      iconAnchor: [16, 32],
      popupAnchor: [0, -32],
    });

    data.forEach((item) => {
      const lat = item.station.tele_station_lat;
      const lon = item.station.tele_station_long;
      const name = item.station.tele_station_name.th;
      const level = item.waterlevel_msl ?? "ไม่มีข้อมูล";
      const diff = item.diff_wl_bank ?? "-";
      const river = item.river_name ?? "-";

      const marker = L.marker([lat, lon], { icon: waterIcon }).bindPopup(`
        <b>${name}</b><br>
        🌊 ระดับน้ำ (MSL): <b>${level} ม.</b><br>
        🏞️ แม่น้ำ/คลอง: ${river}<br>
        🕒 เวลา: ${item.waterlevel_datetime}
      `);

      marker.addTo(waterLayer);
    });
  } catch (error) {
    console.error(error);
  }
}
loadWaterData();

const csvLayer = L.layerGroup();

// =======================
    // กำหนด icon
    // =======================
    const customIcon = L.icon({
      iconUrl: "https://cdn-icons-png.flaticon.com/512/684/684908.png",
      iconSize: [32, 32],
      iconAnchor: [16, 32],
      popupAnchor: [0, -32],
    });

    // =======================
    // โหลดและแสดงข้อมูล CSV
    // =======================
    Papa.parse("sl_monitoring_05010107.csv", {
      download: true,
      header: true,
      complete: function (results) {
        results.data.forEach((row) => {
          const easting = parseFloat(row.UTM_E);
          const northing = parseFloat(row.UTM_N);

          // ตรวจว่าค่าพิกัดถูกต้อง
          if (!isNaN(easting) && !isNaN(northing)) {
            // -------------------------
            // แปลงจาก UTM Zone 47N → Lat/Lng (ประเทศไทยส่วนใหญ่)
            // -------------------------
            const utmProjection = "+proj=utm +zone=47 +datum=WGS84 +units=m +no_defs";
            const wgs84 = "+proj=longlat +datum=WGS84 +no_defs";
            const [lng, lat] = proj4(utmProjection, wgs84, [easting, northing]);

            // สร้าง marker
            const marker = L.marker([lat, lng], { icon: customIcon })
              .bindPopup(`
                <b>สถานี:</b> ${row.STAT_ID || "-"}<br>
                <b>บ้าน:</b> ${row.BAN || "-"}<br>
                <b>ตำบล:</b> ${row.TAMBON || "-"}<br>
                <b>อำเภอ:</b> ${row.DISTRICT || "-"}<br>
                <b>จังหวัด:</b> ${row.PROVINCE || "-"}<br>
                <b>ปี:</b> ${row.YEAR || "-"}<br>
              `);
            csvLayer.addLayer(marker);
          }
        });
      },
    });

    var road = L.tileLayer.wms(
      "http://localhost:8080/geoserver/agi_students/wms",
      {
        layers: "agi_students:road",
        format: "image/png",
        transparent: true,
      }
    );

    var district = L.tileLayer.wms(
      "http://localhost:8080/geoserver/agi_students/wms",
      {
        layers: "agi_students:district",
        format: "image/png",
        transparent: true,
      }
    );
    
  //   let geoLayer; // ✅ ประกาศไว้ก่อน

  // // ดึงข้อมูลจาก PHP Proxy
  // fetch("get_wfs.php")
  //   .then(res => res.json())
  //   .then(data => {
  //     geoLayer = L.geoJSON(data, {
  //       style: { color: "blue", weight: 2, fillOpacity: 0.3 },
  //       onEachFeature: (feature, layer) => {
  //         const props = feature.properties;
  //         let popup = "";
  //         for (const key in props) {
  //           popup += `<b>${key}</b>: ${props[key]}<br>`;
  //         }
  //         layer.bindPopup(popup);
  //       }
  //     });
  //     map.fitBounds(geoLayer.getBounds());
  //   })
  //   .catch(err => console.error("โหลดข้อมูล WFS ไม่ได้:", err));

      // เพิ่มชั้นข้อมูลจาก GeoServer (WMS)
      const orthoLayer = L.tileLayer.wms("http://localhost:8080/geoserver/agi_students/wms", {
        layers: "agi", // ชื่อเลเยอร์
        format: "image/png", // รูปแบบภาพที่ใช้
        transparent: true,   // ให้พื้นหลังโปร่งใส
        version: "1.1.0",    // เวอร์ชันของ WMS
      });

// ==========================
// Layer Control
const baseMaps = {
  OSM: osm,
  Satellite: satellite,
  Terrain: topo,
};
const overlayMaps = {
  "ฝน 24 ชม. (API)": rainLayer,
  "ระดับน้ำ (API)": waterLayer,
  "ขอบเขตจังหวัด (GeoJSON)": ThaiProvJSON,
    "สถานีตรวจวัดการเคลื่อนตัวของมวลดิน (CSV)": csvLayer,
    "ถนนในจังหวัดพิษณุโลก (Gesoserver WMS)": road,
    "ขอบเขตอำเภอในจังหวัดพิษณุโลก (Gesoserver WMS)":district,
    "ภาพ Ortho agi (Gesoserver GeoTIff)":orthoLayer,
};
// ✅ เก็บ Layer Control ไว้ในตัวแปร
const layerControl = L.control.layers(baseMaps, overlayMaps, { collapsed: true }).addTo(map);

// ==========================
// ดึงข้อมูลจาก PHP Proxy (WFS)
fetch("get_wfs.php")
  .then(res => res.json())
  .then(data => {
    // สร้าง geoLayer หลังโหลดเสร็จ
    const geoLayer = L.geoJSON(data, {
      style: { color: "blue", weight: 2, fillOpacity: 0.3 },
      onEachFeature: (feature, layer) => {
        const props = feature.properties;
        let popup = "";
        for (const key in props) {
          popup += `<b>${key}</b>: ${props[key]}<br>`;
        }
        layer.bindPopup(popup);
      }
    });

    // ✅ เพิ่ม layer นี้เข้ากับ Layer Control หลังโหลดเสร็จ
    layerControl.addOverlay(geoLayer, "ป่าสงวนแห่งชาติ (GeoServer WFS)");
  })
  .catch(err => console.error("โหลดข้อมูล WFS ไม่ได้:", err));