@extends('layouts.master')

@section('title', 'Dashboard Pengguna')

@section('content')

{{-- Dashboard Content --}}

@yield('dashboardContent')

<!-- Tombol Melayang
<button onclick="alert('Jika terjadi kendala, silahkan hubungi HR/IT')" 
        class="btn btn-secondary rounded-circle shadow"
        style="position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; z-index: 10000;">
    ?
</button> -->

<script>
  // ==============
  // Dari sini
  // ==============
  const courseColors = {};
  const colorsPalette = [
    "#0d6efd", "#198754", "#dc3545", "#fd7e14", "#6f42c1", "#0dcaf0", "#ffc107"
  ];
  let colorIndex = 0;

  window.coursePeriods.forEach(p => {
    if (!courseColors[p.title]) {
      courseColors[p.title] = colorsPalette[colorIndex % colorsPalette.length];
      colorIndex++;
    }
  });

  function isDateInRange(date, start, end) {
      const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
      const s = new Date(start.getFullYear(), start.getMonth(), start.getDate());
      const e = new Date(end.getFullYear(), end.getMonth(), end.getDate());

      return d >= s && d <= e;
  }

  function renderCalendar() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const days = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
    let html = "";

    days.forEach(d => html += `<div class="header">${d}</div>`);

    for (let i = 0; i < firstDay; i++) html += `<div></div>`;

    for (let day = 1; day <= daysInMonth; day++) {
      const currentDate = new Date(year, month, day);

      const isToday =
        day === now.getDate() &&
        month === now.getMonth() &&
        year === now.getFullYear();

      let titles = [];
      let bgColor = "";

      if (window.coursePeriods?.length) {
        window.coursePeriods.forEach(p => {
          const start = new Date(p.start);
          const end = new Date(p.end);

          if (isDateInRange(currentDate, start, end)) {
            titles.push(p.title);
            // jika ada lebih dari 1 course, ambil yang pertama
            if (!bgColor) bgColor = courseColors[p.title];
          }
        });
      }

      let style = "";
      if (bgColor && !isToday) {
        style = `style="background:${bgColor};color:#fff;"`;
      }

      html += `
        <div class="${isToday ? "today" : ""}" ${style}>
          ${day}
          ${titles.length ? `<div class="course-tooltip">${titles.join("<br>")}</div>` : ""}
        </div>
      `;
    }

    document.getElementById("calendarBox").innerHTML = html;
  }
  // ==============
  // Sampai sini
  // ==============
  function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString("id-ID", {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit"
    });
    const dateString = now.toLocaleDateString("id-ID", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric"
    });

    document.getElementById("todayDate").innerText = dateString;
    document.getElementById("currentTime").innerText = timeString;
  }

  renderCalendar();
  updateTime();
  setInterval(updateTime, 1000);
</script>
@endsection
