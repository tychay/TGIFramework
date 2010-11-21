Name:      libmemcached
Summary:   Client library and command line tools for memcached server
Version:   0.44
Release:   1%{?dist}
License:   BSD
Group:     System Environment/Libraries
URL:       http://tangent.org/552/libmemcached.html
Source0:   http://download.tangent.org/libmemcached-%{version}.tar.gz


# checked during configure (for test suite)
BuildRequires: memcached

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)


%description
libmemcached is a C client library to the memcached server
(http://danga.com/memcached). It has been designed to be light on memory
usage, and provide full access to server side methods.

It also implements several command line tools:

memcat - Copy the value of a key to standard output.
memflush - Flush the contents of your servers.
memrm - Remove a key(s) from the server.
memstat - Dump the stats of your servers to standard output.
memslap - Generate testing loads on a memcached cluster.
memcp - Copy files to memcached servers.
memerror - Creates human readable messages from libmemcached error codes.


%package devel
Summary: Header files and development libraries for %{name}
Group: Development/Libraries
Requires: %{name} = %{version}-%{release}
Requires: pkgconfig

%description devel
This package contains the header files and development libraries
for %{name}. If you like to develop programs using %{name}, 
you will need to install %{name}-devel.


%prep
%setup -q

%{__rm} -f libmemcached/hsieh_hash.c 

%{__mkdir} examples
%{__cp} -p tests/*.{c,cpp,h} examples/


%build
%configure
%{__make} %{_smp_mflags}


%install
%{__rm} -rf %{buildroot}
%{__make} install  DESTDIR="%{buildroot}" AM_INSTALL_PROGRAM_FLAGS=""


%check
# For documentation only:
# test suite cannot run in mock (same port use for memcache servers on all arch)
# All tests completed successfully
# diff output.res output.cmp fails but result depend on server version
#%{__make} test


%clean
%{__rm} -rf %{buildroot}


%post -p /sbin/ldconfig


%postun -p /sbin/ldconfig
 

%files
%defattr (-,root,root,-) 
%doc AUTHORS COPYING README THANKS TODO ChangeLog
%{_bindir}/mem*
# libhashkit, libmemcachedprotocol are new, *.a no longer built -tychay
%exclude %{_libdir}/libhashkit.la
%exclude %{_libdir}/libmemcachedprotocol.la
#%exclude %{_libdir}/libmemcached.a
%exclude %{_libdir}/libmemcached.la
#%exclude %{_libdir}/libmemcachedutil.a
%exclude %{_libdir}/libmemcachedutil.la
%{_libdir}/libhashkit.so.*
%{_libdir}/libmemcachedprotocol.so.*
%{_libdir}/libmemcached.so.*
%{_libdir}/libmemcachedutil.so.*
%{_mandir}/man1/mem*


%files devel
%defattr (-,root,root,-) 
%doc examples
# libhashkit, libmemcachedprotocol added
%{_includedir}/libhashkit
%{_includedir}/libmemcached
%{_libdir}/libhashkit.so
%{_libdir}/libmemcachedprotocol.so
%{_libdir}/libmemcached.so
%{_libdir}/libmemcachedutil.so
%{_libdir}/pkgconfig/libmemcached.pc
%{_mandir}/man3/hashkit_*.3.gz
%{_mandir}/man3/libmemcached*.3.gz
%{_mandir}/man3/memcached_*.3.gz


%changelog
* Sun Nov 21 2010 terry chay <tychay@php.net> - 0.44-1
- update to 0.44
- new libhashkit, libmemcachedprotocol added

* Sun Sep 13 2009 Remi Collet <Fedora@famillecollet.com> - 0.31-1
- update to 0.31

* Fri Jul 24 2009 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 0.30-2
- Rebuilt for https://fedoraproject.org/wiki/Fedora_12_Mass_Rebuild

* Sun Jun 14 2009 Remi Collet <Fedora@famillecollet.com> - 0.30-1
- update to 0.30

* Tue May 19 2009 Remi Collet <Fedora@famillecollet.com> - 0.29-1
- update to 0.29

* Fri May 01 2009 Remi Collet <Fedora@famillecollet.com> - 0.28-2
- add upstream patch to disable nonfree hsieh hash method

* Sat Apr 25 2009 Remi Collet <Fedora@famillecollet.com> - 0.28-1
- Initial RPM from Brian Aker spec
- create -devel subpackage
- add %%post %%postun %%check section

