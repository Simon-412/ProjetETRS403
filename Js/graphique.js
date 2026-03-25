function afficherGraphique() {

    const heures = document.querySelectorAll(".heure");
    const temps = document.querySelectorAll(".temp");
    const hums = document.querySelectorAll(".hum");

    let labels = [];
    let dataTemp = [];
    let dataHum = [];

    for (let i = 0; i < heures.length; i++) {
        labels.push(heures[i].innerText);
        dataTemp.push(parseFloat(temps[i].innerText));
        dataHum.push(parseFloat(hums[i].innerText));
    }

    const ctx = document.getElementById("graph").getContext("2d");

    new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Température",
                    data: dataTemp,
                    borderWidth: 2
                },
                {
                    label: "Humidité",
                    data: dataHum,
                    borderWidth: 2
                }
            ]
        }
    });
}