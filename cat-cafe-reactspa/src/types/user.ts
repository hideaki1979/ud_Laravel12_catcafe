export interface User {
    id: string;
    email: string;
    name: string;
    samlId: string;
    attributes?: Record<string, unknown>;
}
