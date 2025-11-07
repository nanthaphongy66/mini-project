// ==========================
// สร้างแผนที่
// ==========================
const map = L.map("map").setView([16.5, 100.5], 8);

// ==========================
// Base Maps
// ==========================
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
// Layer Group และ Icon
// ==========================
const userPointsLayer = L.layerGroup().addTo(map);

const userIcon = L.icon({
  iconUrl: "./assets/img/pin-point.png",
  iconSize: [32, 32],
  iconAnchor: [16, 32],
  popupAnchor: [0, -32],
});

// ==========================
// ตัวแปรเก็บ Markers
// ==========================
let userMarkers = [];

// ==========================
// ฟังก์ชันสร้าง Popup
// ==========================
function updatePopup(marker, id, name, description) {
  marker.bindPopup(`
    <div style="min-width: 200px;">
      <h3 style="margin: 0 0 10px 0; color: #2c3e50;">${name}</h3>
      <p style="margin: 5px 0; color: #666;">${description}</p>
      <div style="margin-top: 10px; display: flex; gap: 5px;">
        <button id="edit-${id}" style="flex: 1; padding: 5px; background: #3498db; color: white; border: none; border-radius: 3px; cursor: pointer;">✏️ แก้ไข</button>
        <button id="delete-${id}" style="flex: 1; padding: 5px; background: #e74c3c; color: white; border: none; border-radius: 3px; cursor: pointer;">🗑️ ลบ</button>
      </div>
    </div>
  `);

  marker.on("popupopen", function () {
    document.getElementById(`delete-${id}`).onclick = function () {
      deletePoint(id);
    };
    document.getElementById(`edit-${id}`).onclick = function () {
      editPoint(id, name, description, marker);
    };
  });
}

// ==========================
// โหลดและรีเฟรชจุดแบบ Real-time
// ==========================
async function refreshPoints() {
  try {
    // ล้าง layer เดิม
    userPointsLayer.clearLayers();
    userMarkers = [];

    // ✅ แก้ path: ใช้ path จาก HTML file location
    const res = await axios.get("./api.php?action=points");
    
    // ✅ ตรวจสอบว่าได้ข้อมูลเป็น Array หรือไม่
    console.log("API Response:", res.data);
    const points = Array.isArray(res.data) ? res.data : [];
    
    if (!Array.isArray(res.data)) {
      console.warn("API ไม่ได้ส่ง Array กลับมา:", res.data);
    }

    // อัปเดต dropdown
    const select = document.getElementById("searchSelect");
    select.innerHTML = '<option value="">-- เลือกจุดเพื่อค้นหา --</option>';

    // วนลูปสร้าง markers
    points.forEach((p) => {
      const marker = L.marker([p.lat, p.lon], {
        draggable: true,
        icon: userIcon,
      });

      updatePopup(marker, p.id, p.name, p.description);

      // เมื่อลากจุดเสร็จ → อัปเดตในฐานข้อมูล
      marker.on("dragend", async function (e) {
        const { lat, lng } = e.target.getLatLng();
        try {
          await axios.post("./api.php?action=update_point", {
            id: p.id,
            name: p.name,
            description: p.description,
            lat: lat,
            lon: lng,
          });
          alert("ย้ายจุดสำเร็จ!");
          refreshPoints();
        } catch (error) {
          alert("เกิดข้อผิดพลาดในการย้ายจุด");
          console.error(error);
        }
      });

      marker.addTo(userPointsLayer);
      userMarkers.push({ marker: marker, name: p.name, id: p.id });

      // เพิ่มใน dropdown
      const option = document.createElement("option");
      option.value = p.id;
      option.text = p.name;
      select.add(option);
    });

    console.log(`โหลดจุดสำเร็จ: ${points.length} จุด`);
  } catch (error) {
    console.error("เกิดข้อผิดพลาดในการโหลดจุด:", error);
    alert("ไม่สามารถโหลดข้อมูลได้ กรุณาตรวจสอบการเชื่อมต่อ");
  }
}

// โหลดครั้งแรก
refreshPoints();

// ==========================
// เพิ่มจุดใหม่เมื่อคลิกแผนที่
// ==========================
map.on("click", async (e) => {
  const name = prompt("ชื่อจุด:");
  if (!name) return;

  const description = prompt("รายละเอียด:") || "";
  const { lat, lng } = e.latlng;

  try {
    const res = await axios.post("./api.php?action=add_point", {
      name: name,
      description: description,
      lat: lat,
      lon: lng,
    });

    if (res.data.status === "success") {
      alert("เพิ่มจุดสำเร็จ!");
      refreshPoints();
    }
  } catch (error) {
    alert("เกิดข้อผิดพลาดในการเพิ่มจุด");
    console.error(error);
  }
});

// ==========================
// ลบจุด
// ==========================
async function deletePoint(id) {
  if (!confirm("ต้องการลบจุดนี้หรือไม่?")) return;

  try {
    const res = await axios.post("./api.php?action=delete_point", { id });

    if (res.data.status === "success") {
      alert("ลบจุดสำเร็จ!");
      refreshPoints();
    }
  } catch (error) {
    alert("เกิดข้อผิดพลาดในการลบจุด");
    console.error(error);
  }
}

// ==========================
// แก้ไขจุด
// ==========================
async function editPoint(id, oldName, oldDesc, marker) {
  const name = prompt("ชื่อใหม่:", oldName);
  if (!name) return;

  const desc = prompt("รายละเอียดใหม่:", oldDesc) || "";
  const { lat, lng } = marker.getLatLng();

  try {
    const res = await axios.post("./api.php?action=update_point", {
      id,
      name,
      description: desc,
      lat,
      lon: lng,
    });

    if (res.data.status === "success") {
      alert("อัปเดตจุดสำเร็จ!");
      refreshPoints();
    }
  } catch (error) {
    alert("เกิดข้อผิดพลาดในการอัปเดตจุด");
    console.error(error);
  }
}

// ==========================
// Dropdown: เลือก Marker เพื่อค้นหา
// ==========================
document.getElementById("searchSelect").onchange = function () {
  const selectedId = this.value;
  if (!selectedId) return;

  const item = userMarkers.find((x) => x.id == selectedId);
  if (item) {
    map.setView(item.marker.getLatLng(), 16);
    item.marker.openPopup();
  }
};

// ==========================
// ปุ่มรีเซ็ต: กลับไปมุมมองเดิม
// ==========================
document.getElementById("resetBtn").onclick = function () {
  map.setView([16.5, 100.5], 8);
  document.getElementById("searchSelect").value = "";
  map.closePopup();
};

// ==========================
// Layer Control
// ==========================
const baseMaps = {
  "🗺️ OSM": osm,
  "🛰️ Satellite": satellite,
  "⛰️ Terrain": topo,
};

const overlayMaps = {
  "📍 จุดที่บันทึกลง Database": userPointsLayer,
};

L.control.layers(baseMaps, overlayMaps, { collapsed: false }).addTo(map);

// ==========================
// Scale Control
// ==========================
L.control.scale({ imperial: false, metric: true }).addTo(map);

console.log("✅ Script โหลดเสร็จสมบูรณ์");