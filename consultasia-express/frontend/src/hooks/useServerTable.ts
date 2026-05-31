import { useState } from 'react';

export function useServerTable(defaultPageSize = 50) {
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(defaultPageSize);

  function setPageSize_(size: number) {
    setPageSize(size);
    setPage(1); // reset to page 1 on size change
  }

  return { page, pageSize, setPage, setPageSize: setPageSize_ };
}
