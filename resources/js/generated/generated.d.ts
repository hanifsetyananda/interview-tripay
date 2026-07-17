declare namespace App {
    namespace Models {
        export type Invoices = {
            incrementing: boolean;
            preventsLazyLoading: boolean;
            exists: boolean;
            wasRecentlyCreated: boolean;
            timestamps: boolean;
            usesUniqueIds: boolean;
        };
        export type Products = {
            incrementing: boolean;
            preventsLazyLoading: boolean;
            exists: boolean;
            wasRecentlyCreated: boolean;
            timestamps: boolean;
            usesUniqueIds: boolean;
        };
        export type User = {
            incrementing: boolean;
            preventsLazyLoading: boolean;
            exists: boolean;
            wasRecentlyCreated: boolean;
            timestamps: boolean;
            usesUniqueIds: boolean;
        };
    }
}
