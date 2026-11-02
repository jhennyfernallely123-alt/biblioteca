export const SEASONS = {
    winter: { key: 'winter', months: [1] },
    valentine: { key: 'valentine', months: [2] },
    spring: { key: 'spring', months: [3] },
    rain: { key: 'rain', months: [4] },
    flowers: { key: 'flowers', months: [5] },
    summer: { key: 'summer', months: [6] },
    sun: { key: 'sun', months: [7] },
    sunflower: { key: 'sunflower', months: [8] },
    harvest: { key: 'harvest', months: [9] },
    halloween: { key: 'halloween', months: [10] },
    autumn: { key: 'autumn', months: [11] },
    christmas: { key: 'christmas', months: [12] },
};

export const SEASON_KEYS = Object.keys(SEASONS);

export function seasonForMonth(month) {
    const season = Object.values(SEASONS).find((item) => item.months.includes(month));

    return season?.key ?? 'autumn';
}

export function currentSeason(date = new Date()) {
    return seasonForMonth(date.getMonth() + 1);
}
