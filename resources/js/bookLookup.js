export function applyLookupResult(form, book) {
    form.title = book.title ?? '';
    form.author = book.author ?? '';
    form.isbn = book.isbn ?? '';
    form.publisher = book.publisher ?? '';
    form.published_year = book.published_year ?? null;
    form.catalog_book_id = book.catalog_book_id ?? null;
    form.cover_url = book.catalog_book_id ? null : (book.cover_url ?? null);
    form.cover = null;

    return form.cover_url ?? book.cover_url ?? null;
}
