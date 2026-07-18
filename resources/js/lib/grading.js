// Client-side mirror of app/Domain/Grading/Grade.php — for live-typing
// feedback only. The server always recomputes and is the source of truth;
// this must stay boundary-for-boundary identical to the PHP enum. Both are
// tested against the same fixture (see grading.fixture.json and the
// matching tests/Unit/Domain/GradeTest.php data provider).
export const GRADE_POINTS = { A: 5, B: 4, C: 3, D: 2, E: 1, F: 0 };

export const GRADE_COLORS = {
    A: '#0a7d4f',
    B: '#15697c',
    C: '#22407e',
    D: '#8a5b00',
    E: '#a34c0a',
    F: '#b3261e',
};

export function gradeFromMark(mark) {
    if (mark >= 70) return 'A';
    if (mark >= 60) return 'B';
    if (mark >= 50) return 'C';
    if (mark >= 45) return 'D';
    if (mark >= 40) return 'E';
    return 'F';
}

export function gradePointFromMark(mark) {
    return GRADE_POINTS[gradeFromMark(mark)];
}
