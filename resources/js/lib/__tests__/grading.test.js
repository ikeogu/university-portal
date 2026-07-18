import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';
import { gradeFromMark, gradePointFromMark } from '../grading.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// Same fixture consumed by tests/Unit/Domain/GradeTest.php — this file's
// only job is to prove the JS mirror agrees with the PHP source of truth
// boundary-for-boundary, not to re-derive the grading rules independently.
const fixturePath = path.resolve(
    __dirname,
    '../../../../tests/Fixtures/grade_boundaries.json',
);
const fixture = JSON.parse(readFileSync(fixturePath, 'utf-8'));

describe('gradeFromMark matches the PHP Grade enum boundary-for-boundary', () => {
    it.each(fixture.map((row) => [row.case, row.mark, row.grade, row.point]))(
        '%s (mark %d -> %s / %d)',
        (_label, mark, expectedGrade, expectedPoint) => {
            expect(gradeFromMark(mark)).toBe(expectedGrade);
            expect(gradePointFromMark(mark)).toBe(expectedPoint);
        },
    );
});
