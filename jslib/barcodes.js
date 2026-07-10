class Barcodes {
    static #matrix = [
        [0, 3, 1, 7, 5, 9, 8, 6, 4, 2],
        [7, 0, 9, 2, 1, 5, 4, 8, 6, 3],
        [4, 2, 0, 6, 8, 7, 1, 3, 5, 9],
        [1, 7, 5, 0, 9, 8, 3, 4, 2, 6],
        [6, 1, 2, 3, 0, 4, 5, 9, 7, 8],
        [3, 6, 7, 4, 2, 0, 9, 5, 8, 1],
        [5, 8, 6, 9, 7, 2, 0, 1, 3, 4],
        [8, 9, 4, 5, 3, 6, 2, 0, 1, 7],
        [9, 4, 3, 8, 6, 1, 7, 2, 0, 5],
        [2, 5, 8, 1, 4, 3, 6, 7, 9, 0],
        ];

    /**
     * Calculate the checksum digit from provided number
     *
     * @param number
     * @return int
     */
    static encode(number) {
        number = number.toString().trim();
        let interim = 0;
        for (let i = 0; i < number.length; i++) {
            interim = this.#matrix[interim][number[i]];
        }

        return interim;
    }
    static addEncode(number) {
        return number + this.encode(number);
    }

    /**
     * Checks the checksum digit from provided number
     *
     * @param
     * @return bool
     */
    static check(number) {
        return (0 == this.encode(number));
    }

    static trimChecksum(number) {
        return number.substring(0, number.length - 1);
    }
}
