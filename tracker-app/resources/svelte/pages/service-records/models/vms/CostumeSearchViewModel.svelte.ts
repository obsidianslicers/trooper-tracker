import type { CostumeOption } from "../types";

/**
 * The Costume Stats Lookup: matches costumes by name once two letters are typed.
 */
export class CostumeSearchViewModel {
    search: string = $state("");
    showResults: boolean = $state(false);

    constructor(private readonly costumes: CostumeOption[]) {}

    get matches(): CostumeOption[] {
        const query = this.search.trim().toLowerCase();

        if (query.length < 2) {
            return [];
        }

        return this.costumes.filter((costume) => costume.name.toLowerCase().includes(query));
    }

    choose = (costume: CostumeOption) => {
        this.search = costume.name;
        this.showResults = false;
    };
}
